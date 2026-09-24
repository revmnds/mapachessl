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

    // A dispatched job that no worker picked up within this window is considered lost
    public const QUEUE_WAIT_SECONDS = 300;

    protected $casts = [
        'expires_at' => 'datetime',
        'generation_started_at' => 'datetime',
        'job_started_at' => 'datetime',
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
        ]);
    }

    public function markAsFailed(string $error): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $error,
            'generation_started_at' => null,
            'job_started_at' => null,
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
            return $this->job_started_at->diffInSeconds(now()) < self::JOB_TIMEOUT_SECONDS;
        }

        // Dispatched but not picked up by a worker yet
        return $this->generation_started_at->diffInSeconds(now()) < self::QUEUE_WAIT_SECONDS;
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

    public static function activeGenerationsCount(): int
    {
        return self::where('status', 'in_progress')
            ->whereNotNull('generation_started_at')
            ->get(['generation_started_at', 'job_started_at'])
            ->filter(fn (self $r) => $r->isGenerating())
            ->count();
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
