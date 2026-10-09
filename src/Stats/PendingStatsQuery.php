<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Stats;

use Closure;
use DateTimeInterface;
use Generator;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use RoundlyConsulting\Plausible\Enums\Dimension;
use RoundlyConsulting\Plausible\Enums\FilterOperator;
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
use RoundlyConsulting\Plausible\Results\BreakdownRow;
use RoundlyConsulting\Plausible\Results\QueryResult;
use RoundlyConsulting\Plausible\Results\TimeseriesResult;
use RoundlyConsulting\Plausible\Support\ResponseFactory;
use RoundlyConsulting\Plausible\Support\Value;

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

    /** Null until set: the v2 `run()` then sends no page size unless it pages. */
    private ?int $perPage = null;

    private DateTimeInterface|int|null $cacheTtl = null;

    private ?string $cacheStore = null;

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

    /**
     * Add a type-safe Stats v2 filter, e.g.
     * `->where(Dimension::VisitBrowser, FilterOperator::Is, 'Chrome')`.
     * Multiple `where()` calls are combined with an implicit AND. Targets the
     * v2 `run()` terminal.
     */
    public function where(string|Property|Dimension $field, string|FilterOperator $operator, mixed $value): self
    {
        $this->arrayFilters[] = $this->compileFilter($field, $operator, $value);

        return $this;
    }

    /**
     * Combine the previous filter with this one under an OR group.
     */
    public function orWhere(string|Property|Dimension $field, string|FilterOperator $operator, mixed $value): self
    {
        $filter = $this->compileFilter($field, $operator, $value);
        $last = array_pop($this->arrayFilters);

        if ($last === null) {
            $this->arrayFilters[] = $filter;

            return $this;
        }

        if (($last[0] ?? null) === 'or' && isset($last[1]) && is_array($last[1])) {
            $group = $last[1];
            $group[] = $filter;
            $last[1] = $group;
            $this->arrayFilters[] = $last;

            return $this;
        }

        $this->arrayFilters[] = ['or', [$last, $filter]];

        return $this;
    }

    /**
     * @return array<int, mixed>
     */
    private function compileFilter(string|Property|Dimension $field, string|FilterOperator $operator, mixed $value): array
    {
        $values = is_array($value) ? array_values($value) : [$value];

        return [Value::of($operator), Value::of($field), $values];
    }

    public function period(string|Period $period): self
    {
        $this->period = $period;

        return $this;
    }

    public function today(): self
    {
        return $this->period(Period::Day);
    }

    public function yesterday(): self
    {
        return $this->between(
            Carbon::yesterday()->startOfDay(),
            Carbon::yesterday()->endOfDay(),
        );
    }

    public function last7Days(): self
    {
        return $this->period(Period::SevenDays);
    }

    public function last30Days(): self
    {
        return $this->period(Period::ThirtyDays);
    }

    public function last6Months(): self
    {
        return $this->period(Period::SixMonths);
    }

    public function last12Months(): self
    {
        return $this->period(Period::TwelveMonths);
    }

    public function thisMonth(): self
    {
        return $this->period(Period::Month);
    }

    public function lastMonth(): self
    {
        return $this->between(
            Carbon::now()->subMonthNoOverflow()->startOfMonth(),
            Carbon::now()->subMonthNoOverflow()->endOfMonth(),
        );
    }

    public function thisYear(): self
    {
        return $this->period(Period::Year);
    }

    public function allTime(): self
    {
        return $this->period(Period::All);
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

    /**
     * A custom range from `$start` through today (the app's timezone), so both APIs get
     * a closed range: v1 `period=custom&date=start,today`, v2 `date_range: [start, today]`.
     */
    public function since(Carbon $start): self
    {
        return $this->between($start, Carbon::today());
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

    /**
     * Cache the terminal call (aggregate/timeseries/breakdown/run/realtime) for
     * the given TTL, keyed by a deterministic signature of the query. Accepts an
     * int (seconds) or a {@see DateTimeInterface}, plus an optional store name.
     */
    public function cache(DateTimeInterface|int $ttl, ?string $store = null): self
    {
        $this->cacheTtl = $ttl;
        $this->cacheStore = $store;

        return $this;
    }

    public function aggregate(): AggregateResult
    {
        return AggregateResult::fromResponse(
            $this->cachedResponse('aggregate', fn () => $this->stats->aggregate($this->aggregateRequest())),
        );
    }

    public function timeseries(): TimeseriesResult
    {
        return TimeseriesResult::fromResponse(
            $this->cachedResponse('timeseries', fn () => $this->stats->timeseries($this->timeseriesRequest())),
        );
    }

    public function breakdown(string|Property|null $property = null): BreakdownResult
    {
        $this->property = $property ?? $this->property;

        if ($this->property === null) {
            throw MissingConfiguration::site();
        }

        $resolved = $this->property;

        return BreakdownResult::fromResponse(
            $this->cachedResponse('breakdown', fn () => $this->stats->breakdown($this->breakdownRequest($resolved))),
        );
    }

    /**
     * Lazily walk every page of a breakdown, yielding each row. Pages are
     * fetched on demand so large breakdowns never load all at once.
     *
     * @return Generator<int, BreakdownRow>
     */
    public function cursor(string|Property|null $property = null): Generator
    {
        $property ??= $this->property;

        if ($property === null) {
            throw MissingConfiguration::site();
        }

        $page = 1;

        do {
            $this->page = $page;
            $rows = $this->breakdown($property)->rows();

            foreach ($rows as $row) {
                yield $row;
            }

            $page++;
        } while ($rows !== [] && count($rows) >= $this->resolvePerPage());
    }

    /**
     * Walk every breakdown row, invoking the callback for each. Return `false`
     * from the callback to stop early.
     *
     * @param  callable(BreakdownRow): (bool|void)  $callback
     */
    public function each(callable $callback, string|Property|null $property = null): void
    {
        foreach ($this->cursor($property) as $row) {
            if ($callback($row) === false) {
                break;
            }
        }
    }

    public function realtime(): int
    {
        if ($this->cacheTtl === null) {
            return $this->stats->realtime($this->resolveSite());
        }

        return (int) Cache::store($this->cacheStore)->remember(
            $this->cacheKey('realtime'),
            $this->cacheTtl,
            fn (): int => $this->stats->realtime($this->resolveSite()),
        );
    }

    /**
     * Run the accumulated state against the Stats v2 `/api/v2/query` endpoint.
     */
    public function run(): QueryResult
    {
        if ($this->cacheTtl === null) {
            return $this->stats->runQuery($this->queryRequest());
        }

        $payload = $this->rememberPayload(
            'query',
            fn () => $this->stats->runQuery($this->queryRequest())->response()->json(),
        );

        return QueryResult::fromResponse(ResponseFactory::json($payload));
    }

    private function aggregateRequest(): AggregateRequest
    {
        return new AggregateRequest(
            site: $this->resolveSite(),
            metrics: $this->resolveMetrics(),
            filters: $this->filters,
            period: $this->period ?? '30d',
            start: $this->start,
            end: $this->end,
            compare: $this->compare,
        );
    }

    private function timeseriesRequest(): TimeseriesRequest
    {
        return new TimeseriesRequest(
            site: $this->resolveSite(),
            metrics: $this->resolveMetrics(),
            filters: $this->filters,
            interval: $this->interval ?? 'date',
            period: $this->period ?? '30d',
            start: $this->start,
            end: $this->end,
        );
    }

    private function breakdownRequest(string|Property $property): BreakdownRequest
    {
        return new BreakdownRequest(
            site: $this->resolveSite(),
            property: $property,
            metrics: $this->resolveMetrics(),
            filters: $this->filters,
            interval: $this->interval ?? 'date',
            period: $this->period ?? '30d',
            start: $this->start,
            end: $this->end,
            page: $this->page,
            perPage: $this->resolvePerPage(),
        );
    }

    /**
     * Plausible's v2 default page size is 10000, so the size is sent whenever it was set or
     * an offset is sent — `page(2)` alone must still mean rows 101–200.
     */
    private function queryRequest(): QueryRequest
    {
        $offset = $this->page > 1 ? ($this->page - 1) * $this->resolvePerPage() : null;

        return new QueryRequest(
            site: $this->resolveSite(),
            metrics: $this->resolveMetrics(),
            dateRange: $this->resolveDateRange(),
            dimensions: $this->dimensions,
            filters: $this->arrayFilters,
            orderBy: $this->orderBy,
            include: $this->include,
            limit: $this->perPage !== null || $offset !== null ? $this->resolvePerPage() : null,
            offset: $offset,
        );
    }

    /**
     * Run a Response-returning fetch, caching its decoded body when a TTL is set.
     *
     * @param  Closure(): Response  $fetch
     */
    private function cachedResponse(string $type, Closure $fetch): Response
    {
        if ($this->cacheTtl === null) {
            return $fetch();
        }

        return ResponseFactory::json($this->rememberPayload($type, fn () => $fetch()->json()));
    }

    /**
     * @param  Closure(): mixed  $fetch
     * @return array<mixed>
     */
    private function rememberPayload(string $type, Closure $fetch): array
    {
        return Cache::store($this->cacheStore)->remember(
            $this->cacheKey($type),
            $this->cacheTtl,
            static function () use ($fetch): array {
                $value = $fetch();

                return is_array($value) ? $value : [];
            },
        );
    }

    private function cacheKey(string $type): string
    {
        $state = [
            'type' => $type,
            'site' => $this->resolveSite(),
            'metrics' => Value::list($this->resolveMetrics()),
            'filters' => $this->filters,
            'arrayFilters' => $this->arrayFilters,
            'dimensions' => Value::list($this->dimensions),
            'orderBy' => $this->orderBy,
            'include' => $this->include,
            'period' => $this->period instanceof Period ? $this->period->value : $this->period,
            'interval' => $this->interval instanceof Interval ? $this->interval->value : $this->interval,
            'start' => $this->start?->format('Y-m-d'),
            'end' => $this->end?->format('Y-m-d'),
            'compare' => $this->compare,
            'property' => $this->property instanceof Property ? $this->property->value : $this->property,
            'page' => $this->page,
            'perPage' => $this->perPage,
        ];

        return 'plausible:stats:'.md5((string) json_encode($state));
    }

    private function resolvePerPage(): int
    {
        return $this->perPage ?? 100;
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
