<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CertificateRequest extends Model
{
    protected $fillable = [
        'session_token',
        'domain',
        'is_wildcard',
        'challenge_type',
        'challenge_token',
        'challenge_filename',
        'current_step',
        'status',
        'generation_started_at',
        'generation_attempt',
        'job_started_at',
        'last_seen_at',
        'heartbeat_at',
        'retry_count',
        'error_message',
        'certificate_pem',
        'private_key_pem',
        'chain_pem',
        'expires_at',
    ];

    // Max time a job may run: two ACME attempts (stale auth retry) share this budget
    public const JOB_BUDGET_SECONDS = 3600;
    public const JOB_TIMEOUT_SECONDS = 3900;

    // A running job beats every few seconds; silence this long means it was killed
    // (redeploy, crash) and the user can retry instead of waiting for the full timeout
    public const HEARTBEAT_STALE_SECONDS = 90;

    // Waiting in line: the page must stay open (it polls every few seconds).
    // Gone longer than this, the request loses its place.
    public const LINE_ABANDON_SECONDS = 300;

    // Upper bound for waiting in line, in case no worker ever picks the job up
    public const LINE_MAX_WAIT_SECONDS = 3600;

    protected $casts = [
        'expires_at' => 'datetime',
        'generation_started_at' => 'datetime',
        'job_started_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'heartbeat_at' => 'datetime',
        'is_wildcard' => 'boolean',
        'private_key_pem' => 'encrypted',
    ];

    public static function findByToken(string $token): ?self
    {
        return self::where('session_token', $token)
            ->where('status', 'in_progress')
            ->first();
    }

    public static function findByTokenAny(string $token): ?self
    {
        return self::where('session_token', $token)->first();
    }

    public static function createNew(): self
    {
        return self::create([
            'session_token' => bin2hex(random_bytes(32)),
        ]);
    }

    public function generateChallengeToken(): void
    {
        $token = bin2hex(random_bytes(32));
        $filename = bin2hex(random_bytes(16));

        $this->update([
            'challenge_token' => $token,
            'challenge_filename' => $filename,
        ]);
    }

    public function isComplete(): bool
    {
        return $this->status === 'completed';
    }

    public function isHttpChallenge(): bool
    {
        return $this->challenge_type === 'http';
    }

    public function isDnsChallenge(): bool
    {
        return $this->challenge_type === 'dns';
    }

    public function getHttpChallengeUrl(): string
    {
        return "http://{$this->domain}/.well-known/acme-challenge/{$this->challenge_filename}";
    }

    public function getHttpChallengePath(): string
    {
        return ".well-known/acme-challenge/{$this->challenge_filename}";
    }

    public function getDnsRecordName(): string
    {
        return "_acme-challenge.{$this->domain}";
    }

    public function markAsCompleted(string $cert, string $key, string $chain, \DateTime $expiresAt): void
    {
        $this->update([
            'status' => 'completed',
            'certificate_pem' => $cert,
            'private_key_pem' => $key,
            'chain_pem' => $chain,
            'expires_at' => $expiresAt,
            'current_step' => 4,
            'generation_started_at' => null,
            'job_started_at' => null,
            'heartbeat_at' => null,
        ]);
    }

    public function markAsFailed(string $error): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $error,
            'generation_started_at' => null,
            'job_started_at' => null,
            'heartbeat_at' => null,
        ]);
    }

    public function resetForRetry(): void
    {
        $this->update([
            'status' => 'in_progress',
            'error_message' => null,
            'challenge_token' => null,
            'challenge_filename' => null,
            'retry_count' => $this->retry_count + 1,
        ]);
    }

    public function isGenerating(): bool
    {
        if (!$this->generation_started_at) {
            return false;
        }

        if ($this->job_started_at) {
            return $this->job_started_at->diffInSeconds(now()) < self::JOB_TIMEOUT_SECONDS
                && $this->heartbeat_at
                && $this->heartbeat_at->diffInSeconds(now()) < self::HEARTBEAT_STALE_SECONDS;
        }

        // Waiting in line for a free worker
        return $this->generation_started_at->diffInSeconds(now()) < self::LINE_MAX_WAIT_SECONDS
            && !$this->hasLeftLine();
    }

    public function isWaitingInLine(): bool
    {
        return $this->status === 'in_progress' && $this->generation_started_at && !$this->job_started_at;
    }

    public function hasLeftLine(): bool
    {
        return !$this->last_seen_at || $this->last_seen_at->diffInSeconds(now()) >= self::LINE_ABANDON_SECONDS;
    }

    /**
     * Requests waiting for a worker whose visitor is still around. Workers take jobs
     * in dispatch order, which matches generation_started_at.
     */
    public static function waitingInLine()
    {
        return self::where('status', 'in_progress')
            ->whereNotNull('generation_started_at')
            ->whereNull('job_started_at')
            ->where('generation_started_at', '>', now()->subSeconds(self::LINE_MAX_WAIT_SECONDS))
            ->where('last_seen_at', '>', now()->subSeconds(self::LINE_ABANDON_SECONDS));
    }

    public static function runningCount(): int
    {
        return self::where('status', 'in_progress')
            ->where('job_started_at', '>', now()->subSeconds(self::JOB_TIMEOUT_SECONDS))
            ->where('heartbeat_at', '>', now()->subSeconds(self::HEARTBEAT_STALE_SECONDS))
            ->count();
    }

    /**
     * How many people are ahead in line (0 = next), or null when not waiting
     */
    public function linePosition(): ?int
    {
        // A free worker polls every 3s: don't flash "you're next" before it picks the job up
        if (!$this->isWaitingInLine() || $this->generation_started_at->diffInSeconds(now()) < 5) {
            return null;
        }

        // Timestamps have second precision: break ties by id
        return self::waitingInLine()
            ->where(fn ($q) => $q
                ->where('generation_started_at', '<', $this->generation_started_at)
                ->orWhere(fn ($q) => $q
                    ->where('generation_started_at', $this->generation_started_at)
                    ->where('id', '<', $this->id)))
            ->count();
    }

    /**
     * Record that the visitor's page is still open (throttled to avoid a write per poll)
     */
    public function touchLastSeen(): void
    {
        if (!$this->last_seen_at || $this->last_seen_at->diffInSeconds(now()) >= 20) {
            $this->update(['last_seen_at' => now()]);
        }
    }

    /**
     * Mark the request as locked for a new generation attempt.
     * The attempt id lets a job detect it was superseded by a newer one.
     */
    public function lockGeneration(): string
    {
        $attempt = (string) \Illuminate\Support\Str::uuid();

        $this->update([
            'generation_started_at' => now(),
            'generation_attempt' => $attempt,
            'job_started_at' => null,
            'last_seen_at' => now(),
        ]);

        return $attempt;
    }

    public function unlockGeneration(): void
    {
        $this->update([
            'generation_started_at' => null,
            'generation_attempt' => null,
        ]);
    }

    /**
     * Whether this record still belongs to the given generation attempt
     */
    public function isCurrentAttempt(string $attempt): bool
    {
        return $this->status === 'in_progress' && $this->generation_attempt === $attempt;
    }

    public function hasCertificate(): bool
    {
        return !empty($this->certificate_pem) && !empty($this->private_key_pem);
    }

    public function isWildcard(): bool
    {
        return (bool) $this->is_wildcard;
    }

    public function getDisplayDomain(): string
    {
        return $this->is_wildcard ? "*." . $this->domain : $this->domain;
    }

    public function getAllDomains(): array
    {
        if ($this->is_wildcard) {
            return [$this->domain, "*." . $this->domain];
        }
        return [$this->domain];
    }
}
