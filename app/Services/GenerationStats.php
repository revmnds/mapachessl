<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Per-day counters for the daily Telegram summary. Best effort: a cache failure
 * never interrupts the generation being counted.
 */
class GenerationStats
{
    public const METRICS = ['completed', 'failed', 'failed_timeout', 'failed_rate_limit', 'busy', 'peak_load'];

    private const TTL_SECONDS = 3 * 86400;

    public static function increment(string $metric): void
    {
        $key = self::key(self::today(), $metric);

        try {
            Cache::add($key, 0, self::TTL_SECONDS);
            Cache::increment($key);
        } catch (\Throwable) {
        }
    }

    public static function max(string $metric, int $value): void
    {
        $key = self::key(self::today(), $metric);

        try {
            if ($value > (int) Cache::get($key, 0)) {
                Cache::put($key, $value, self::TTL_SECONDS);
            }
        } catch (\Throwable) {
        }
    }

    /**
     * @return array<string, int>
     */
    public static function forDay(string $date): array
    {
        return collect(self::METRICS)
            ->mapWithKeys(fn ($metric) => [$metric => (int) Cache::get(self::key($date, $metric), 0)])
            ->all();
    }

    private static function today(): string
    {
        return now(config('services.telegram.timezone'))->toDateString();
    }

    private static function key(string $date, string $metric): string
    {
        return "stats:{$date}:{$metric}";
    }
}
