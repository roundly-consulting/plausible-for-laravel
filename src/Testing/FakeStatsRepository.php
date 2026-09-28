<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Testing;

use Illuminate\Http\Client\Response;
use RoundlyConsulting\Plausible\Repositories\StatsRepository;
use RoundlyConsulting\Plausible\Requests\Stats\AggregateRequest;
use RoundlyConsulting\Plausible\Requests\Stats\BreakdownRequest;
use RoundlyConsulting\Plausible\Requests\Stats\QueryRequest;
use RoundlyConsulting\Plausible\Requests\Stats\TimeseriesRequest;
use RoundlyConsulting\Plausible\Results\QueryResult;

/**
 * Recording {@see StatsRepository} used by {@see PlausibleFake}. It records each
 * query for later assertions, then performs the (faked) HTTP call.
 *
 * @internal
 */
final class FakeStatsRepository extends StatsRepository
{
    public function __construct(
        private readonly PlausibleFake $fake,
    ) {}

    public function realtime(string $site): int
    {
        $this->fake->recordQuery('realtime', $site);

        return parent::realtime($site);
    }

    public function aggregate(AggregateRequest $request): Response
    {
        $this->fake->recordQuery('aggregate', $request);

        return parent::aggregate($request);
    }

    public function timeseries(TimeseriesRequest $request): Response
    {
        $this->fake->recordQuery('timeseries', $request);

        return parent::timeseries($request);
    }

    public function breakdown(BreakdownRequest $request): Response
    {
        $this->fake->recordQuery('breakdown', $request);

        return parent::breakdown($request);
    }

    public function runQuery(QueryRequest $request): QueryResult
    {
        $this->fake->recordQuery('query', $request);

        return parent::runQuery($request);
    }
}
