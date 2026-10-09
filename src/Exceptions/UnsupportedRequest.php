<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Exceptions;

/**
 * Thrown before anything is sent when a request asks for something the target Plausible
 * endpoint does not support, where the API would answer 400 or silently ignore it.
 */
final class UnsupportedRequest extends PlausibleException
{
    public static function allTimeOnV1(): self
    {
        return new self(
            'The `all` period is not supported by the v1 Stats API (aggregate(), timeseries(), '.
            'breakdown()). Use run(), the Stats API v2, which accepts allTime().'
        );
    }
}
