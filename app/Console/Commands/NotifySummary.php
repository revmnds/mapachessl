<?php

namespace App\Console\Commands;

use App\Services\GenerationStats;
use App\Services\TelegramNotifier;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class NotifySummary extends Command
{
    protected $signature = 'notify:summary {date? : Day to report (Y-m-d), yesterday by default}';
    protected $description = 'Send the daily generation summary to Telegram';

    public function handle(TelegramNotifier $telegram): int
    {
        $date = $this->argument('date')
            ?? now(config('services.telegram.timezone'))->subDay()->toDateString();
        $stats = GenerationStats::forDay($date);

        $failed = "Fallidos: {$stats['failed']}";
        $reasons = array_filter([
            $stats['failed_timeout'] ? "{$stats['failed_timeout']} sin configurar a tiempo" : null,
            $stats['failed_rate_limit'] ? "{$stats['failed_rate_limit']} por rate limit" : null,
        ]);
        if ($reasons) {
            $failed .= ' (' . implode(', ', $reasons) . ')';
        }

        $capacity = (int) config('services.acme.workers') + (int) config('services.acme.max_queue');

        $telegram->send(implode("\n", [
            '<b>Resumen del ' . Carbon::parse($date)->format('d/m/Y') . '</b>',
            "Generados: {$stats['completed']}",
            $failed,
            "Rechazados por cola llena: {$stats['busy']}",
            "Máximo simultáneo: {$stats['peak_load']} de {$capacity}",
        ]));

        return self::SUCCESS;
    }
}
