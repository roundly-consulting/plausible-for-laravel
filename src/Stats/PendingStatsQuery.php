<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Stats;

use Illuminate\Support\Carbon;
use RoundlyConsulting\Plausible\Enums\Dimension;
use RoundlyConsulting\Plausible\Enums\Interval;
use RoundlyConsulting\Plausible\Enums\Metric;
use RoundlyConsulting\Plausible\Enums\Period;
use RoundlyConsulting\Plausible\Enums\Property;
use RoundlyConsulting\Plausible\Exceptions\MissingConfiguration;
use RoundlyConsulting\Plausible\Repositories\StatsRepository;
use RoundlyConsulting\Plausible\Requests\Stats\AggregateRequest;
use RoundlyConsulting\Plausible\Requests\Stats\BreakdownRequest;
use RoundlyConsulting\Plausible\Requests\Stats\QueryRequest;
use RoundlyConsulting\Plausible\Requests\Stats\TimeseriesRequest;
use RoundlyConsulting\Plausible\Results\AggregateResult;
use RoundlyConsulting\Plausible\Results\BreakdownResult;
use RoundlyConsulting\Plausible\Results\QueryResult;
use RoundlyConsulting\Plausible\Results\TimeseriesResult;

/**
 * Fluent builder over the Stats API. Accumulates state and, on a terminal call,
 * constructs the matching request object and hands it to the repository — a thin
 * façade over the same HTTP layer the explicit request objects use.
 */
final class PendingStatsQuery
{
    /** @var list<string|Metric> */
    private array $metrics = [];

    /** @var list<string> */
    private array $filters = [];

    /** @var list<array<int, mixed>> */
    private array $arrayFilters = [];

    /** @var list<string|Dimension> */
    private array $dimensions = [];

    /** @var list<array<int, string>> */
    private array $orderBy = [];

    /** @var array<string, mixed> */
    private array $include = [];

    private string|Period|null $period = null;

    private string|Interval|null $interval = null;

    private ?Carbon $start = null;

    private ?Carbon $end = null;

    private bool $compare = false;

    private string|Property|null $property = null;

    private int $page = 1;

    private int $perPage = 100;

    public function __construct(
        private readonly StatsRepository $stats,
        private ?string $site = null,
    ) {}

    public function for(string $site): self
    {
        $this->site = $site;

        return $this;
    }

    public function metrics(string|Metric ...$metrics): self
    {
        $this->metrics = array_values($metrics);

        return $this;
    }

    /**
     * Add a filter. Strings use the legacy filter syntax (for aggregate/
     * timeseries/breakdown); arrays use the Stats v2 form (for `query()`).
     *
     * @param  string|array<int, mixed>  $filter
     */
    public function filter(string|array $filter): self
    {
        if (is_string($filter)) {
            $this->filters[] = $filter;
        } else {
            $this->arrayFilters[] = $filter;
        }

        return $this;
    }

    public function period(string|Period $period): self
    {
        $this->period = $period;

        return $this;
    }

    public function interval(string|Interval $interval): self
    {
        $this->interval = $interval;

        return $this;
    }

    public function between(Carbon $start, Carbon $end): self
    {
        $this->start = $start;
        $this->end = $end;
        $this->period = Period::Custom;

        return $this;
    }

    public function since(Carbon $start): self
    {
        $this->start = $start;
        $this->period = Period::Custom;

        return $this;
    }

    public function compare(bool $compare = true): self
    {
        $this->compare = $compare;

        return $this;
    }

    public function property(string|Property $property): self
    {
        $this->property = $property;

        return $this;
    }

    public function page(int $page): self
    {
        $this->page = $page;

        return $this;
    }

    public function perPage(int $perPage): self
    {
        $this->perPage = $perPage;

        return $this;
    }

    public function dimensions(string|Dimension ...$dimensions): self
    {
        $this->dimensions = array_values($dimensions);

        return $this;
    }

    /**
     * @param  array<int, string>  ...$orderBy
     */
    public function orderBy(array ...$orderBy): self
    {
        $this->orderBy = array_values($orderBy);

        return $this;
    }

    /**
     * @param  array<string, mixed>  $include
     */
    public function include(array $include): self
    {
        $this->include = $include;

        return $this;
    }

    public function aggregate(): AggregateResult
    {
        return AggregateResult::fromResponse(
            $this->stats->aggregate(new AggregateRequest(
                site: $this->resolveSite(),
                metrics: $this->resolveMetrics(),
                filters: $this->filters,
                period: $this->period ?? '30d',
                start: $this->start,
                end: $this->end,
                compare: $this->compare,
            )),
        );
    }

    public function timeseries(): TimeseriesResult
    {
        return TimeseriesResult::fromResponse(
            $this->stats->timeseries(new TimeseriesRequest(
                site: $this->resolveSite(),
                metrics: $this->resolveMetrics(),
                filters: $this->filters,
                interval: $this->interval ?? 'date',
                period: $this->period ?? '30d',
                start: $this->start,
                end: $this->end,
            )),
        );
    }

    public function breakdown(string|Property|null $property = null): BreakdownResult
    {
        $property ??= $this->property;

        if ($property === null) {
            throw MissingConfiguration::site();
        }

        return BreakdownResult::fromResponse(
            $this->stats->breakdown(new BreakdownRequest(
                site: $this->resolveSite(),
                property: $property,
                metrics: $this->resolveMetrics(),
                filters: $this->filters,
                interval: $this->interval ?? 'date',
                period: $this->period ?? '30d',
                start: $this->start,
                end: $this->end,
                page: $this->page,
                perPage: $this->perPage,
            )),
        );
    }

    public function realtime(): int
    {
        return $this->stats->realtime($this->resolveSite());
    }

    /**
     * Run the accumulated state against the Stats v2 `/api/v2/query` endpoint.
     */
    public function run(): QueryResult
    {
        return $this->stats->runQuery(new QueryRequest(
            site: $this->resolveSite(),
            metrics: $this->resolveMetrics(),
            dateRange: $this->resolveDateRange(),
            dimensions: $this->dimensions,
            filters: $this->arrayFilters,
            orderBy: $this->orderBy,
            include: $this->include,
            limit: $this->perPage === 100 ? null : $this->perPage,
            offset: $this->page > 1 ? ($this->page - 1) * $this->perPage : null,
        ));
    }

    /**
     * @return list<string|Metric>
     */
    private function resolveMetrics(): array
    {
        return $this->metrics === [] ? ['visitors'] : $this->metrics;
    }

    private function resolveSite(): string
    {
        return $this->site ?? $this->stats->defaultSite() ?? throw MissingConfiguration::site();
    }

    /**
     * @return string|list<string>
     */
    private function resolveDateRange(): string|array
    {
        if ($this->start instanceof Carbon && $this->end instanceof Carbon) {
            return [$this->start->format('Y-m-d'), $this->end->format('Y-m-d')];
        }

        $period = $this->period ?? '30d';
        $value = $period instanceof Period ? $period->value : $period;

        return $value === Period::Custom->value ? '30d' : $value;
    }
}
