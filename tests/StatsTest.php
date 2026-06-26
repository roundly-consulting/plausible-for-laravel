<?php

declare(strict_types=1);

use Illuminate\Http\Client\Response;
use RoundlyConsulting\Plausible\Requests\Stats\AggregateRequest;
use RoundlyConsulting\Plausible\Requests\Stats\BreakdownRequest;
use RoundlyConsulting\Plausible\Requests\Stats\TimeseriesRequest;

it('returns realtime visitors count', function () {
    useHttpSnapshot('stats/realtime');

    $visitors = plausible()->stats()->realtime('localhost');

    expect($visitors)->toBe(10);
});

it('returns aggregated metrics using filters', function () {
    useHttpSnapshot('stats/aggregate');

    $metrics = plausible()
        ->stats()
        ->aggregate(AggregateRequest::make(
            site: 'localhost',
            metrics: [
                'visitors',
                'pageviews',
                'bounce_rate',
                'visit_duration',
                'events',
                'visits',
            ],
            period: 'custom',
            start: now()->startOfMonth()->subMonths(2),
            end: now()->startOfMonth()->subMonth(),
            compare: true,
        ));

    expect($metrics)->toBeInstanceOf(Response::class);
});

it('returns timeseries', function () {
    useHttpSnapshot('stats/timeseries');

    $metrics = plausible()
        ->stats()
        ->timeseries(TimeseriesRequest::make(
            site: 'localhost'
        ));

    expect($metrics)->toBeInstanceOf(Response::class);
});

it('returns breakdown', function () {
    useHttpSnapshot('stats/breakdown');

    $metrics = plausible()
        ->stats()
        ->breakdown(BreakdownRequest::make(
            site: 'localhost',
            property: 'event:page',
        ));

    expect($metrics)->toBeInstanceOf(Response::class);
});
