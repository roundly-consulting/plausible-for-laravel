<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Requests\Stats;

use Illuminate\Support\Carbon;
use RoundlyConsulting\Plausible\Enums\Interval;
use RoundlyConsulting\Plausible\Enums\Metric;
use RoundlyConsulting\Plausible\Enums\Period;
use RoundlyConsulting\Plausible\Exceptions\UnsupportedRequest;
use RoundlyConsulting\Plausible\Requests\Concerns\InteractsWithDateRange;
use RoundlyConsulting\Plausible\Requests\Query;
use RoundlyConsulting\Plausible\Support\Value;

/**
 * A v1 timeseries request. Plausible's v1 endpoint buckets only by `date` (alias `day`) or
 * `month`; any other interval is refused when the request is built — hourly or weekly
 * buckets need the v2 `run()` with a `time:*` dimension.
 */
final class TimeseriesRequest
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
        public readonly string|Interval $interval = 'date',
        public readonly string|Period $period = '30d',
        public readonly ?Carbon $start = null,
        public readonly ?Carbon $end = null,
    ) {
        $this->ensureV1Period();

        if (! in_array(Value::of($interval), ['date', 'day', Interval::Month->value], true)) {
            throw UnsupportedRequest::timeseriesInterval(Value::of($interval));
        }
    }

    /**
     * @param  list<string|Metric>  $metrics
     * @param  list<string>  $filters
     */
    public static function make(
        string $site,
        array $metrics = ['visitors'],
        array $filters = [],
        string|Interval $interval = 'date',
        string|Period $period = '30d',
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
            ->set('metrics', implode(',', Value::list($this->metrics)))
            ->set('period', $this->v1Period())
            ->set('interval', Value::of($this->interval))
            ->when($this->hasFilters(), 'filters', implode(';', $this->filters))
            ->when($this->sendsV1Date(), 'date', fn (): string => $this->formatDateRange());
    }
}
