<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Exceptions;

use RoundlyConsulting\PackageToolkit\Concerns\ProvidesRetryAfter;
use RoundlyConsulting\PackageToolkit\Contracts\HasRetryAfter;

/**
 * Thrown when a throttled request would wait longer than the surface's
 * configured `max_wait` ceiling, so callers can fail fast instead of blocking.
 *
 * Extends {@see PlausibleException} so `catch (PlausibleException $e)` still
 * catches it; carries the exhausted budget key locally, while the seconds until
 * the window frees up ride on the toolkit's HasRetryAfter contract — so a host
 * can turn any rate-limited failure into a `Retry-After` header without knowing
 * about this package.
 */
final class RateLimitExceeded extends PlausibleException implements HasRetryAfter
{
    use ProvidesRetryAfter;

    public function __construct(
        string $message,
        public readonly string $key,
    ) {
        parent::__construct($message);
    }

    public static function for(string $key, int $retryAfterSeconds): self
    {
        $exception = new self(
            "Rate limit for [{$key}] exceeded. Retry in {$retryAfterSeconds} second(s).",
            $key,
        );

        return $exception->withRetryAfter($retryAfterSeconds);
    }
}
