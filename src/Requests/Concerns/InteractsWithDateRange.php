<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Requests\Concerns;

use Illuminate\Support\Carbon;
use RoundlyConsulting\Plausible\Enums\Period;
use RoundlyConsulting\Plausible\Support\Value;

/**
 * Shared date-range handling for stats requests. The consuming request exposes
 * readonly {@see Carbon} `$start` / `$end` and `string|Period $period` properties.
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
     * The v1 `date` parameter. A custom period needs `start,end` — Plausible rejects a
     * lone date there — so an open-ended custom range is closed at today (the app's
     * timezone). Any other period takes the start as its single anchor date
     * (`period=month&date=2024-01-01` is January 2024).
     */
    protected function formatDateRange(): string
    {
        $start = $this->start?->format('Y-m-d') ?? '';

        if ($this->end instanceof Carbon) {
            return $start.','.$this->end->format('Y-m-d');
        }

        if (Value::of($this->period) === Period::Custom->value) {
            return $start.','.Carbon::today()->format('Y-m-d');
        }

        return $start;
    }
}
