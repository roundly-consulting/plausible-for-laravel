<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Enums;

/**
 * Relative time periods accepted by the legacy Stats API `period` parameter.
 * Use {@see Period::Custom} together with a start/end date for arbitrary ranges.
 */
enum Period: string
{
    case Day = 'day';
    case SevenDays = '7d';
    case ThirtyDays = '30d';
    case Month = 'month';
    case SixMonths = '6mo';
    case TwelveMonths = '12mo';
    case Year = 'year';
    case All = 'all';
    case Custom = 'custom';
}
