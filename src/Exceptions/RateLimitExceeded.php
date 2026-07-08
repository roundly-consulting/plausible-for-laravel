<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Exceptions;

/**
 * Thrown when a throttled request would wait longer than the surface's
 * configured `max_wait` ceiling, so callers can fail fast instead of blocking.
 *
 * Extends {@see PlausibleException} so `catch (PlausibleException $e)` still
 * catches it; carries the exhausted budget key and the seconds until the window
 * frees up.
 */
final class RateLimitExceeded extends PlausibleException
{
    public function __construct(
        string $message,
        public readonly string $key,
        public readonly int $availableInSeconds,
    ) {
        parent::__construct($message);
    }

    public static function for(string $key, int $availableInSeconds): self
    {
        return new self(
            "Rate limit for [{$key}] exceeded. Retry in {$availableInSeconds} second(s).",
            $key,
            $availableInSeconds,
        );
    }
}
