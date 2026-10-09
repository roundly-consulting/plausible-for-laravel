<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Requests\Concerns;

use Illuminate\Support\Carbon;
use RoundlyConsulting\Plausible\Enums\Period;
use RoundlyConsulting\Plausible\Exceptions\UnsupportedRequest;
use RoundlyConsulting\Plausible\Support\Value;

/**
 * Shared date-range handling for stats requests. The consuming request exposes
 * readonly {@see Carbon} `$start` / `$end` and `string|Period $period` properties.
 *
 * The v1 endpoints accept only the day, 7d, 30d, month, 6mo, 12mo and custom periods.
 * `year` is therefore sent as a custom range (1 January through today, unless the request
 * carries its own dates), and `all` is refused when the request is built.
 */
trait InteractsWithDateRange
{
    public function usesCustomStartPoint(): bool
    {
        return $this->start instanceof Carbon;
    }

    public function usesCustomEndPoint(): bool
    {
        return $this->end instanceof Carbon;
    }

    /**
     * @throws UnsupportedRequest when the period is `all`, which has no v1 form
     */
    protected function ensureV1Period(): void
    {
        if (Value::of($this->period) === Period::All->value) {
            throw UnsupportedRequest::allTimeOnV1();
        }
    }

    /**
     * The v1 `period` parameter: `year` goes out as `custom`.
     */
    protected function v1Period(): string
    {
        $period = Value::of($this->period);

        return $period === Period::Year->value ? Period::Custom->value : $period;
    }

    /**
     * Whether the v1 `date` parameter is sent: with a start date, or for `year`.
     */
    protected function sendsV1Date(): bool
    {
        return $this->usesCustomStartPoint() || Value::of($this->period) === Period::Year->value;
    }

    /**
     * The v1 `date` parameter. A custom period needs `start,end` — Plausible rejects a
     * lone date there — so an open-ended custom range is closed at today (the app's
     * timezone), and `year` without dates is 1 January through today. Any other period
     * takes the start as its single anchor date (`period=month&date=2024-01-01` is
     * January 2024).
     */
    protected function formatDateRange(): string
    {
        $today = Carbon::today()->format('Y-m-d');

        if (! $this->start instanceof Carbon) {
            return Carbon::today()->startOfYear()->format('Y-m-d').','.$today;
        }

        $start = $this->start->format('Y-m-d');

        if ($this->end instanceof Carbon) {
            return $start.','.$this->end->format('Y-m-d');
        }

        if ($this->v1Period() === Period::Custom->value) {
            return $start.','.$today;
        }

        return $start;
    }
}
