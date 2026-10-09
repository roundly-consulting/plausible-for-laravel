<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Enums;

use Illuminate\Support\Str;
use RoundlyConsulting\Enums\Helpers;

/**
 * Relative time periods for the Stats API. Use {@see Period::Custom} together with a
 * start/end date for arbitrary ranges. The v1 endpoints (aggregate/timeseries/breakdown)
 * have no `year` or `all`: `Year` is sent to them as a custom range from 1 January through
 * today, and `All` works only with the v2 `run()` terminal.
 */
enum Period: string
{
    use Helpers;

    case Day = 'day';
    case SevenDays = '7d';
    case ThirtyDays = '30d';
    case Month = 'month';
    case SixMonths = '6mo';
    case TwelveMonths = '12mo';
    case Year = 'year';
    case All = 'all';
    case Custom = 'custom';

    /**
     * The wire values are numeric abbreviations (`7d`, `6mo`, `12mo`), so derive
     * the display label from the case name instead. Wire `value`, `values()`,
     * and `validationRule()` still emit the raw API strings.
     */
    public function readable(): string
    {
        return (string) __(Str::headline($this->name));
    }
}
