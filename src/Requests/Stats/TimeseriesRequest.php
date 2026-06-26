<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Requests\Stats;

use Illuminate\Support\Carbon;
use RoundlyConsulting\Plausible\Requests\Concerns\InteractsWithDateRange;
use RoundlyConsulting\Plausible\Requests\Query;

final class TimeseriesRequest
{
    use InteractsWithDateRange;

    /**
     * @param  list<string>  $metrics
     * @param  list<string>  $filters
     */
    public function __construct(
        public readonly string $site,
        public readonly array $metrics = ['visitors'],
        public readonly array $filters = [],
        public readonly string $interval = 'date',
        public readonly string $period = '30d',
        public readonly ?Carbon $start = null,
        public readonly ?Carbon $end = null,
    ) {}

    /**
     * @param  list<string>  $metrics
     * @param  list<string>  $filters
     */
    public static function make(
        string $site,
        array $metrics = ['visitors'],
        array $filters = [],
        string $interval = 'date',
        string $period = '30d',
        ?Carbon $start = null,
        ?Carbon $end = null,
    ): self {
        return new self($site, $metrics, $filters, $interval, $period, $start, $end);
    }

    public function hasFilters(): bool
    {
        return count($this->filters) > 0;
    }

    public function toQuery(): Query
    {
        return Query::make()
            ->set('site_id', $this->site)
            ->set('metrics', implode(',', $this->metrics))
            ->set('period', $this->period)
            ->set('interval', $this->interval)
            ->when($this->hasFilters(), 'filters', implode(';', $this->filters))
            ->when($this->usesCustomStartPoint(), 'date', fn (): string => $this->formatDateRange());
    }
}
