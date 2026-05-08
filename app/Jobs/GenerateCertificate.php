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

    public int $timeout = 2100; // ~35 min (all tokens verified in parallel, max 30 min wait + buffer)
    public int $tries = 1;

    public function __construct(
        private int $certRequestId
    ) {}

    public function handle(AcmeService $acme): void
    {
        $certRequest = CertificateRequest::find($this->certRequestId);

        if (!$certRequest || $certRequest->status !== 'in_progress') {
            Log::info('GenerateCertificate job: record not found or not in_progress, aborting', [
                'id' => $this->certRequestId,
            ]);
            return;
        }

        Log::info('GenerateCertificate job: starting', ['domain' => $certRequest->domain]);

        $forceNewAuth = $certRequest->retry_count > 0 || $certRequest->is_wildcard;
        $result = $acme->generateCertificate($certRequest, $forceNewAuth);

        // Auto-retry once on stale authorization (cached auth expired at Let's Encrypt)
        if (!$result['success'] && $acme->isStaleAuthorizationError($result['raw_error'] ?? '')) {
            Log::warning('GenerateCertificate job: stale authorization, retrying with fresh order', [
                'domain' => $certRequest->domain,
            ]);
            $acme = new AcmeService();
            $result = $acme->generateCertificate($certRequest, forceNewAuth: true);
        }

        // Re-check record still exists (may have been cancelled during generation)
        $certRequest = CertificateRequest::find($this->certRequestId);
        if (!$certRequest) {
            Log::info('GenerateCertificate job: record deleted during generation (cancelled)');
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
