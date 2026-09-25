<?php

namespace App\Console\Commands;

use App\Services\TelegramNotifier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class NotifyDeploy extends Command
{
    protected $signature = 'notify:deploy';
    protected $description = 'Tell the Telegram chat which version just started (run once per container start)';

    private const REPOSITORY = 'revmnds/mapachessl';

    public function handle(TelegramNotifier $telegram): int
    {
        if (!$telegram->enabled()) {
            return self::SUCCESS;
        }

        $revision = $this->revision();

        if (!$revision) {
            $telegram->send("<b>App iniciada</b>\nVersión desconocida: la imagen no trae los refs de git.");
            return self::SUCCESS;
        }

        $previous = Cache::get('deploy:revision');
        Cache::forever('deploy:revision', $revision);

        if ($revision === $previous) {
            // Same version starting again: crash, host reboot or forced redeploy
            $telegram->send("<b>App reiniciada</b>\nMisma versión: " . substr($revision, 0, 7));
            return self::SUCCESS;
        }

        $url = 'https://github.com/' . self::REPOSITORY
            . ($previous ? "/compare/{$previous}...{$revision}" : "/commit/{$revision}");

        $telegram->send(implode("\n", [
            '<b>Deploy completado</b>',
            ...($this->commitLines($previous, $revision) ?: [substr($revision, 0, 7)]),
            e($url),
        ]));

        return self::SUCCESS;
    }

    /**
     * Commit the image was built from. .dockerignore lets only HEAD and the refs into the image.
     */
    private function revision(): ?string
    {
        $git = base_path('.git');
        $head = trim((string) @file_get_contents("{$git}/HEAD"));

        if (str_starts_with($head, 'ref: ')) {
            $ref = substr($head, 5);
            $head = trim((string) @file_get_contents("{$git}/{$ref}"));

            if ($head === '' && preg_match('/^([0-9a-f]{40}) ' . preg_quote($ref, '/') . '$/m', (string) @file_get_contents("{$git}/packed-refs"), $m)) {
                $head = $m[1];
            }
        }

        return preg_match('/^[0-9a-f]{40}$/', $head) ? $head : null;
    }

    /**
     * "abc1234 Subject" per deployed commit, oldest first; empty when GitHub can't tell
     */
    private function commitLines(?string $previous, string $revision): array
    {
        $api = 'https://api.github.com/repos/' . self::REPOSITORY;

        try {
            $commits = $previous
                ? Http::timeout(5)->get("{$api}/compare/{$previous}...{$revision}")->throw()->json('commits')
                : [Http::timeout(5)->get("{$api}/commits/{$revision}")->throw()->json()];
        } catch (\Throwable) {
            return [];
        }

        return collect($commits)
            ->take(-10)
            ->map(fn ($c) => substr($c['sha'], 0, 7) . ' ' . e(strtok($c['commit']['message'], "\n")))
            ->all();
    }
}
