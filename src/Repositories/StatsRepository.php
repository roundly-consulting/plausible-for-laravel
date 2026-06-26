<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Repositories;

use Illuminate\Http\Client\Response;
use RoundlyConsulting\Plausible\Requests\Stats\AggregateRequest;
use RoundlyConsulting\Plausible\Requests\Stats\BreakdownRequest;
use RoundlyConsulting\Plausible\Requests\Stats\QueryRequest;
use RoundlyConsulting\Plausible\Requests\Stats\TimeseriesRequest;
use RoundlyConsulting\Plausible\Results\AggregateResult;
use RoundlyConsulting\Plausible\Results\BreakdownResult;
use RoundlyConsulting\Plausible\Results\QueryResult;
use RoundlyConsulting\Plausible\Results\TimeseriesResult;
use RoundlyConsulting\Plausible\Stats\PendingStatsQuery;

class StatsRepository extends Repository
{
    /**
     * Start a fluent query for a specific site.
     */
    public function for(string $site): PendingStatsQuery
    {
        return new PendingStatsQuery($this, $site);
    }

    /**
     * Start a fluent query, falling back to the configured default site.
     */
    public function query(): PendingStatsQuery
    {
        return new PendingStatsQuery($this, $this->defaultSite());
    }

    public function realtime(string $site): int
    {
        return (int) $this->request()
            ->get('/api/v1/stats/realtime/visitors', [
                'site_id' => $site,
            ])
            ->body();
    }

    public function aggregate(AggregateRequest $request): Response
    {
        return $this->request()
            ->get('/api/v1/stats/aggregate', $request->toQuery()->toArray());
    }

    public function aggregateResult(AggregateRequest $request): AggregateResult
    {
        return AggregateResult::fromResponse($this->aggregate($request));
    }

    public function timeseries(TimeseriesRequest $request): Response
    {
        return $this->request()
            ->get('/api/v1/stats/timeseries', $request->toQuery()->toArray());
    }

    public function timeseriesResult(TimeseriesRequest $request): TimeseriesResult
    {
        return TimeseriesResult::fromResponse($this->timeseries($request));
    }

    public function breakdown(BreakdownRequest $request): Response
    {
        return $this->request()
            ->get('/api/v1/stats/breakdown', $request->toQuery()->toArray());
    }

    public function breakdownResult(BreakdownRequest $request): BreakdownResult
    {
        return BreakdownResult::fromResponse($this->breakdown($request));
    }

    /**
     * Run a Stats v2 `/api/v2/query` request.
     */
    public function runQuery(QueryRequest $request): QueryResult
    {
        return QueryResult::fromResponse(
            $this->request()->post('/api/v2/query', $request->toBody()),
        );
    }
}
