<?php

namespace App\Services;

use App\Models\CertificateRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Operator alerts to a Telegram chat. Never throws: a failed alert must not break
 * the generation or request that triggered it.
 */
class TelegramNotifier
{
    public function enabled(): bool
    {
        return config('services.telegram.token') && config('services.telegram.chat_id');
    }

    /**
     * @param string $html Telegram HTML: escape anything that isn't markup with e()
     */
    public function send(string $html, bool $preview = false): void
    {
        if (!$this->enabled()) {
            return;
        }

        if (!app()->isProduction()) {
            $html = '[' . e(app()->environment()) . '] ' . $html;
        }

        $token = config('services.telegram.token');

        try {
            Http::connectTimeout(3)->timeout(5)
                ->post("https://api.telegram.org/bot{$token}/sendMessage", [
                    'chat_id' => config('services.telegram.chat_id'),
                    'text' => $html,
                    'parse_mode' => 'HTML',
                    'link_preview_options' => ['is_disabled' => !$preview],
                ])
                ->throw();
        } catch (\Throwable $e) {
            // Connection errors include the URL, and with it the token
            Log::warning('Telegram notification failed', ['error' => str_replace($token, '***', $e->getMessage())]);
        }
    }

    /**
     * Send at most once per $seconds for the same $key
     */
    public function sendThrottled(string $key, int $seconds, string $html): void
    {
        if ($this->enabled() && $this->claim($key, $seconds)) {
            $this->send($html);
        }
    }

    public function certificateIssued(CertificateRequest $request, ?\DateTimeInterface $startedAt, ?\DateTimeInterface $jobStartedAt): void
    {
        $lines = ['<b>Certificado generado</b>', $this->describe($request)];

        if ($startedAt) {
            $took = 'Tardó ' . $this->duration(now()->getTimestamp() - $startedAt->getTimestamp());
            $waited = $jobStartedAt ? $jobStartedAt->getTimestamp() - $startedAt->getTimestamp() : 0;
            $lines[] = $waited >= 5 ? "{$took} ({$this->duration($waited)} en cola)" : $took;
        }

        $this->send(implode("\n", $lines));
    }

    /**
     * @param string $kind AcmeService::failureKind()
     */
    public function generationFailed(CertificateRequest $request, string $rawError, string $kind): void
    {
        $this->send(implode("\n", [
            $kind === 'rate_limit' ? "<b>Rate limit de Let's Encrypt</b>" : '<b>Falló la generación</b>',
            $this->describe($request),
            '<pre>' . e(mb_strimwidth($rawError, 0, 1000, '...')) . '</pre>',
        ]));
    }

    public function queueFull(): void
    {
        $workers = (int) config('services.acme.workers');
        $line = (int) config('services.acme.max_queue');

        $this->sendThrottled('queue-full', 900, implode("\n", [
            '<b>Cola llena</b>',
            "Se rechazó una solicitud: {$workers} workers ocupados y {$line} en espera.",
            'No se repite este aviso en 15 min.',
        ]));
    }

    public function exception(\Throwable $e): void
    {
        // Redeploys restart Postgres under running workers: only alert when it stays unreachable
        if ($this->isDatabaseDown($e) && !$this->lasted('database-down', 90)) {
            return;
        }

        $where = str_replace(base_path() . '/', '', $e->getFile()) . ':' . $e->getLine();
        $context = app()->runningInConsole()
            ? 'artisan ' . ($_SERVER['argv'][1] ?? '')
            : request()->method() . ' /' . ltrim(request()->path(), '/');

        $this->sendThrottled('exception:' . md5(get_class($e) . $where), 600, implode("\n", [
            '<b>Error no controlado</b>',
            e(get_class($e)) . ' en ' . e($where),
            e($context),
            '<pre>' . e(mb_strimwidth($e->getMessage(), 0, 800, '...')) . '</pre>',
        ]));
    }

    /**
     * "ejemplo.com, *.ejemplo.com (DNS), intento 2"
     */
    private function describe(CertificateRequest $request): string
    {
        $details = [e(implode(', ', $request->getAllDomains())) . ' (' . strtoupper($request->challenge_type ?? '') . ')'];

        if ($request->retry_count > 0) {
            $details[] = 'intento ' . ($request->retry_count + 1);
        }

        if (config('services.acme.staging')) {
            $details[] = 'staging';
        }

        return implode(', ', $details);
    }

    private function duration(int $seconds): string
    {
        return $seconds < 60 ? "{$seconds} s" : intdiv($seconds, 60) . ' min';
    }

    /**
     * Connection failures (SQLSTATE class 08) and Postgres shutting down (57P0x)
     */
    private function isDatabaseDown(\Throwable $e): bool
    {
        return $e instanceof \PDOException && preg_match('/^(08|57P0)/', (string) $e->getCode());
    }

    /**
     * Whether a recurring condition has lasted $seconds; it starts over after going quiet as long
     */
    private function lasted(string $key, int $seconds): bool
    {
        $file = $this->stateFile("since-{$key}");
        clearstatcache(true, $file);

        if ((int) @filemtime($file) < time() - $seconds) {
            @file_put_contents($file, time());
            @chmod($file, 0666);
            return false;
        }

        @touch($file);
        return (int) @file_get_contents($file) <= time() - $seconds;
    }

    private function claim(string $key, int $seconds): bool
    {
        $file = $this->stateFile('throttle-' . md5($key));
        clearstatcache(true, $file);

        if ((int) @filemtime($file) > time() - $seconds) {
            return false;
        }

        @touch($file);
        @chmod($file, 0666);
        return true;
    }

    /**
     * Files in the storage volume shared by app, queue and scheduler, not the cache: alerts matter
     * most when the database is down. World-writable because workers run as root and php-fpm doesn't.
     */
    private function stateFile(string $name): string
    {
        return storage_path("framework/cache/telegram-{$name}");
    }
}
