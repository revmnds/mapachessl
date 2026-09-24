<?php

namespace App\Jobs;

use App\Models\CertificateRequest;
use App\Services\AcmeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateCertificate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = CertificateRequest::JOB_TIMEOUT_SECONDS;
    public int $tries = 1;

    public function __construct(
        private int $certRequestId,
        private string $attempt,
    ) {}

    public function handle(AcmeService $acme): void
    {
        $certRequest = CertificateRequest::find($this->certRequestId);

        if (!$certRequest || !$certRequest->isCurrentAttempt($this->attempt)) {
            Log::info('GenerateCertificate job: record gone or attempt superseded, aborting', [
                'id' => $this->certRequestId,
            ]);
            return;
        }

        // Its turn came but the visitor closed the page while waiting in line
        if ($certRequest->hasLeftLine()) {
            $certRequest->markAsFailed(__('messages.errors.queue_abandoned'));
            Log::info('GenerateCertificate job: visitor left the line, skipping', ['id' => $this->certRequestId]);
            return;
        }

        $certRequest->update(['job_started_at' => now(), 'heartbeat_at' => now()]);

        $acme->setHeartbeat(fn () => CertificateRequest::where('id', $this->certRequestId)
            ->where('generation_attempt', $this->attempt)
            ->update(['heartbeat_at' => now()]));

        Log::info('GenerateCertificate job: starting', ['domain' => $certRequest->domain]);

        $deadline = time() + CertificateRequest::JOB_BUDGET_SECONDS;
        $forceNewAuth = $certRequest->retry_count > 0 || $certRequest->is_wildcard;
        $result = $acme->generateCertificate($certRequest, $this->attempt, $deadline, $forceNewAuth);

        // Auto-retry once on stale authorization (cached auth expired at Let's Encrypt)
        if (!$result['success'] && $acme->isStaleAuthorizationError($result['raw_error'] ?? '')) {
            Log::warning('GenerateCertificate job: stale authorization, retrying with fresh order', [
                'domain' => $certRequest->domain,
            ]);
            CertificateRequest::where('id', $this->certRequestId)
                ->where('generation_attempt', $this->attempt)
                ->update(['challenge_token' => null, 'challenge_filename' => null]);
            $acme = (new AcmeService())->setHeartbeat(fn () => CertificateRequest::where('id', $this->certRequestId)
                ->where('generation_attempt', $this->attempt)
                ->update(['heartbeat_at' => now()]));
            $result = $acme->generateCertificate($certRequest, $this->attempt, $deadline, forceNewAuth: true);
        }

        // Re-check the record still belongs to this attempt (cancelled, restarted or marked stale)
        $certRequest = CertificateRequest::find($this->certRequestId);
        if (!$certRequest || !$certRequest->isCurrentAttempt($this->attempt)) {
            Log::info('GenerateCertificate job: attempt no longer current, discarding result');
            return;
        }

        if ($result['success']) {
            $certRequest->markAsCompleted(
                $result['certificate'],
                $result['private_key'],
                $result['chain'],
                new \DateTime($result['expires_at'])
            );

            Log::info('GenerateCertificate job: certificate generated successfully');
        } else {
            if ($result['error'] === '') {
                Log::info('GenerateCertificate job: cancelled by user');
                return;
            }

            $certRequest->markAsFailed($result['error']);

            Log::error('GenerateCertificate job: generation failed', ['error' => $result['error']]);
        }
    }
}
