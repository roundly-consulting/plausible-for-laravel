<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Requests\Stats;

use Illuminate\Support\Carbon;
use RoundlyConsulting\Plausible\Requests\Concerns\InteractsWithDateRange;
use RoundlyConsulting\Plausible\Requests\Query;

final class BreakdownRequest
{
    use InteractsWithDateRange;

    /**
     * @param  list<string>  $metrics
     * @param  list<string>  $filters
     */
    public function __construct(
        public readonly string $site,
        public readonly string $property,
        public readonly array $metrics = ['visitors'],
        public readonly array $filters = [],
        public readonly string $interval = 'date',
        public readonly string $period = '30d',
        public readonly ?Carbon $start = null,
        public readonly ?Carbon $end = null,
        public readonly int $page = 1,
        public readonly int $perPage = 100,
    ) {}

    /**
     * @param  list<string>  $metrics
     * @param  list<string>  $filters
     */
    public static function make(
        string $site,
        string $property,
        array $metrics = ['visitors'],
        array $filters = [],
        string $interval = 'date',
        string $period = '30d',
        ?Carbon $start = null,
        ?Carbon $end = null,
        int $page = 1,
        int $perPage = 100,
    ): self {
        return new self($site, $property, $metrics, $filters, $interval, $period, $start, $end, $page, $perPage);
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
            ->set('property', $this->property)
            ->set('limit', $this->perPage)
            ->set('page', $this->page)
            ->when($this->hasFilters(), 'filters', implode(';', $this->filters))
            ->when($this->usesCustomStartPoint(), 'date', fn (): string => $this->formatDateRange());
    }
}
