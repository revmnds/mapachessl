<?php

namespace App\Console\Commands;

use App\Models\CertificateRequest;
use Illuminate\Console\Command;

class CleanupCertificateRequests extends Command
{
    protected $signature = 'cleanup:certificates';
    protected $description = 'Remove old and abandoned certificate requests';

    public function handle(): int
    {
        // Abandoned sessions (no domain entered, older than 1 hour)
        $abandoned = CertificateRequest::where('status', 'in_progress')
            ->whereNull('domain')
            ->where('created_at', '<', now()->subHour())
            ->delete();

        // Stale in-progress (domain entered but no active generation, older than 24 hours)
        $stale = CertificateRequest::where('status', 'in_progress')
            ->whereNotNull('domain')
            ->where('updated_at', '<', now()->subDay())
            ->delete();

        // Old failed requests (older than 7 days)
        $failed = CertificateRequest::where('status', 'failed')
            ->where('updated_at', '<', now()->subDays(7))
            ->delete();

        // Old completed requests (older than 30 days)
        $completed = CertificateRequest::where('status', 'completed')
            ->where('updated_at', '<', now()->subDays(30))
            ->delete();

        $total = $abandoned + $stale + $failed + $completed;

        if ($total > 0) {
            $this->info("Cleaned up {$total} records: {$abandoned} abandoned, {$stale} stale, {$failed} failed, {$completed} completed.");
        } else {
            $this->info('No records to clean up.');
        }

        return Command::SUCCESS;
    }
}
