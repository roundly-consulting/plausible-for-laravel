<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Concerns;

use Closure;
use Illuminate\Http\Client\Response;
use RoundlyConsulting\HttpClientRateLimits\Enums\Timespan;
use RoundlyConsulting\HttpClientRateLimits\Exceptions\RateLimitExceededException as HttpRateLimitExceededException;
use RoundlyConsulting\HttpClientRateLimits\Limit;
use RoundlyConsulting\HttpClientRateLimits\RateLimit;
use RoundlyConsulting\Plausible\Exceptions\RateLimitExceeded;

trait InteractsWithRateLimits
{
    /**
     * Build the client-side rate limiter for one API surface (`stats`, `events`
     * or `sites`) from its `plausible.rate_limits.{surface}` config block, or
     * null when the host has disabled throttling for that surface.
     *
     * Requests are paced (the limiter waits until the window frees up) rather
     * than hard-failing; set a `max_wait` to fail fast instead. With `adaptive`
     * on (the default) the limiter also honours Plausible's own `Retry-After`
     * header on a 429.
     */
    protected function rateLimiter(string $surface, ?string $site): ?RateLimit
    {
        /** @var array<string, mixed> $config */
        $config = config("plausible.rate_limits.{$surface}", []);

        if (($config['enabled'] ?? true) === false) {
            return null;
        }

        $timespan = Timespan::tryFrom((string) ($config['per'] ?? 'hour')) ?? Timespan::Hour;
        $owner = (string) ($config['owner'] ?? 'app');

        $rateLimit = RateLimit::make(new Limit(
            maxAttempts: (int) ($config['limit'] ?? 600),
            timespan: $timespan,
        ))->by($this->rateLimitKey($surface, $owner, $site, (bool) ($config['per_site'] ?? true)));

        if (($config['adaptive'] ?? true) === true) {
            $rateLimit->adaptive();
        }

        if (isset($config['max_wait']) && is_numeric($config['max_wait'])) {
            $rateLimit->maxWait((int) $config['max_wait']);
        }

        if (isset($config['jitter']) && is_numeric($config['jitter'])) {
            $rateLimit->jitter((int) $config['jitter']);
        }

        return $rateLimit;
    }

    /**
     * Send a request through the surface's rate limiter. The callback returns
     * the raw {@see Response} (429 and all) so hcrl's adaptive path can read the
     * server's `Retry-After` before we raise on a failed response; the
     * `Response::throw()` therefore fires *after* the limiter has recorded any
     * penalty. hcrl's own exhaustion exception is translated into the package's
     * typed {@see RateLimitExceeded} so hosts catch a meaningful native type.
     *
     * @param  Closure(): Response  $send
     */
    protected function throttled(string $surface, ?string $site, Closure $send): Response
    {
        $rateLimit = $this->rateLimiter($surface, $site);

        if ($rateLimit === null) {
            return $send()->throw();
        }

        try {
            /** @var Response $response */
            $response = $rateLimit->handle($send);
        } catch (HttpRateLimitExceededException $exception) {
            throw RateLimitExceeded::for(
                key: $rateLimit->getKey(),
                availableInSeconds: (int) ceil($exception->delayMs / 1000),
            );
        }

        return $response->throw();
    }

    /**
     * Identity used to key a surface's client-side budget:
     * `plausible:{surface}:{owner}:{host}[:{site}]`. `host` comes from the
     * configured instance URL so Cloud and self-hosted never share a window;
     * `site` is appended when `per_site` is on (drop it for account-wide keys).
     */
    protected function rateLimitKey(string $surface, string $owner, ?string $site, bool $perSite): string
    {
        $host = parse_url((string) config('plausible.url'), PHP_URL_HOST);
        $host = is_string($host) && $host !== '' ? $host : 'plausible.io';

        $key = "plausible:{$surface}:{$owner}:{$host}";

        if ($perSite && is_string($site) && $site !== '') {
            $key .= ":{$site}";
        }

        return $key;
    }
}
