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

    public static function siteTimezone(): self
    {
        return new self(
            'Plausible\'s Sites API cannot change a site\'s timezone: PUT /api/v1/sites/:site_id '.
            'accepts only a new `domain` and ignores `timezone`. Change the timezone in the site '.
            'settings on Plausible instead.'
        );
    }

    public static function emptySiteUpdate(): self
    {
        return new self(
            'A site update needs a new `domain`, the only field Plausible\'s '.
            'PUT /api/v1/sites/:site_id changes.'
        );
    }
}
