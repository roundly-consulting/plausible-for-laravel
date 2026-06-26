<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Requests\Concerns;

use Illuminate\Support\Carbon;

/**
 * Shared date-range handling for stats requests. The consuming request exposes
 * readonly {@see Carbon} `$start` / `$end` properties.
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

    protected function formatDateRange(): string
    {
        $start = $this->start?->format('Y-m-d') ?? '';

        if ($this->end instanceof Carbon) {
            return $start.','.$this->end->format('Y-m-d');
        }

        return $start;
    }
}
