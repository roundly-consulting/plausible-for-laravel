<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Requests\Stats;

use Illuminate\Support\Carbon;
use RoundlyConsulting\Plausible\Enums\Metric;
use RoundlyConsulting\Plausible\Enums\Period;
use RoundlyConsulting\Plausible\Requests\Concerns\InteractsWithDateRange;
use RoundlyConsulting\Plausible\Requests\Query;
use RoundlyConsulting\Plausible\Support\Value;

final class AggregateRequest
{
    use InteractsWithDateRange;

    /**
     * @param  list<string|Metric>  $metrics
     * @param  list<string>  $filters
     */
    public function __construct(
        public readonly string $site,
        public readonly array $metrics = ['visitors'],
        public readonly array $filters = [],
        public readonly string|Period $period = '30d',
        public readonly ?Carbon $start = null,
        public readonly ?Carbon $end = null,
        public readonly bool $compare = false,
    ) {
        $this->ensureV1Period();
    }

    /**
     * @param  list<string|Metric>  $metrics
     * @param  list<string>  $filters
     */
    public static function make(
        string $site,
        array $metrics = ['visitors'],
        array $filters = [],
        string|Period $period = '30d',
        ?Carbon $start = null,
        ?Carbon $end = null,
        bool $compare = false,
    ): self {
        return new self($site, $metrics, $filters, $period, $start, $end, $compare);
    }

    public function hasFilters(): bool
    {
        return count($this->filters) > 0;
    }

    public function toQuery(): Query
    {
        return Query::make()
            ->set('site_id', $this->site)
            ->set('metrics', implode(',', Value::list($this->metrics)))
            ->set('period', $this->v1Period())
            ->when($this->compare, 'compare', 'previous_period')
            ->when($this->hasFilters(), 'filters', implode(';', $this->filters))
            ->when($this->sendsV1Date(), 'date', fn (): string => $this->formatDateRange());
    }
}
