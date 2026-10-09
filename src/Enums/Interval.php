<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Enums;

use RoundlyConsulting\Enums\Helpers;

/**
 * Bucket sizes for timeseries and breakdown requests. The v1 timeseries endpoint accepts
 * only `Date` and `Month`; for hourly or weekly buckets use `run()` with
 * {@see Dimension::TimeHour} / {@see Dimension::TimeWeek}.
 */
enum Interval: string
{
    use Helpers;

    case Minute = 'minute';
    case Hour = 'hour';
    case Date = 'date';
    case Week = 'week';
    case Month = 'month';
}
