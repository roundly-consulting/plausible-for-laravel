<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Repositories;

use Illuminate\Http\Client\Response;
use RoundlyConsulting\Plausible\Requests\Stats\AggregateRequest;
use RoundlyConsulting\Plausible\Requests\Stats\BreakdownRequest;
use RoundlyConsulting\Plausible\Requests\Stats\TimeseriesRequest;

final class StatsRepository extends Repository
{
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

    public function timeseries(TimeseriesRequest $request): Response
    {
        return $this->request()
            ->get('/api/v1/stats/timeseries', $request->toQuery()->toArray());
    }

    public function breakdown(BreakdownRequest $request): Response
    {
        return $this->request()
            ->get('/api/v1/stats/breakdown', $request->toQuery()->toArray());
    }
}
