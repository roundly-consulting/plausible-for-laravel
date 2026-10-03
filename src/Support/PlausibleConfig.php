<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Support;

use RoundlyConsulting\HttpClientRateLimits\Enums\Timespan;
use RoundlyConsulting\PackageToolkit\Exceptions\InvalidConfigurationException;
use RoundlyConsulting\PackageToolkit\Support\Config;

/**
 * Strict reads of the non-boolean `plausible.*` settings. An absent (null) key takes its
 * default; a present value of the wrong shape — `'five'` for the timeout, an `hourly` window,
 * a blank script URL — throws {@see InvalidConfigurationException} naming the key. Nothing is
 * cast to 0 (a junk timeout used to mean "wait forever") or swapped for the default.
 *
 * @internal
 */
final class PlausibleConfig
{
    /** Request timeout in seconds; at least 1. */
    public static function timeout(): int
    {
        return Config::integer('plausible.timeout', 10, min: 1);
    }

    /** Automatic retries for transient failures; 0 = off. */
    public static function retryTimes(): int
    {
        return Config::integer('plausible.retry.times', 0, min: 0);
    }

    /** Milliseconds between retries. */
    public static function retrySleep(): int
    {
        return Config::integer('plausible.retry.sleep_ms', 100, min: 0);
    }

    public static function trackingUserAgent(): string
    {
        return self::string('plausible.tracking.user_agent', 'PlausibleForLaravel');
    }

    public static function trackingIpAddress(): string
    {
        return self::string('plausible.tracking.ip_address', '127.0.0.1');
    }

    public static function scriptSrc(): string
    {
        return self::string('plausible.script.src', 'https://plausible.io/js/script.js');
    }

    /** The `data-domain` override; null (or an empty env line) defers to `plausible.site`. */
    public static function scriptDomain(): ?string
    {
        $domain = config('plausible.script.domain');

        if ($domain === null || $domain === '') {
            return null;
        }

        return Config::requireString('plausible.script.domain');
    }

    /**
     * One surface's rate-limit block, validated. `$section` is the block's full key.
     *
     * @param  array<string, mixed>  $config
     * @return array{per: Timespan, limit: int, owner: string, max_wait: int|null, jitter: int|null}
     */
    public static function rateLimit(string $section, array $config, Timespan $per, int $limit): array
    {
        $values = Config::for([
            "{$section}.per" => $config['per'] ?? null,
            "{$section}.limit" => $config['limit'] ?? null,
            "{$section}.owner" => $config['owner'] ?? null,
            "{$section}.max_wait" => $config['max_wait'] ?? null,
            "{$section}.jitter" => $config['jitter'] ?? null,
        ]);

        return [
            'per' => $values->enum("{$section}.per", Timespan::class, $per),
            'limit' => $values->integer("{$section}.limit", $limit, min: 1),
            'owner' => ($config['owner'] ?? null) === null ? 'app' : $values->requireString("{$section}.owner"),
            'max_wait' => ($config['max_wait'] ?? null) === null ? null : $values->integer("{$section}.max_wait", 0, min: 0),
            'jitter' => ($config['jitter'] ?? null) === null ? null : $values->integer("{$section}.jitter", 0, min: 0),
        ];
    }

    private static function string(string $key, string $default): string
    {
        return config($key) === null ? $default : Config::requireString($key);
    }
}
