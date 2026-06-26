<?php

declare(strict_types=1);

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RoundlyConsulting\Plausible\Requests\Stats\AggregateRequest;
use RoundlyConsulting\Plausible\Requests\Stats\BreakdownRequest;
use RoundlyConsulting\Plausible\Requests\Stats\TimeseriesRequest;

it('returns realtime visitors count', function () {
    useHttpSnapshot('stats/realtime');

    $visitors = plausible()->stats()->realtime('localhost');

    expect($visitors)->toBe(10);

    Http::assertSent(
        fn ($request) => str_contains($request->url(), '/api/v1/stats/realtime/visitors')
            && $request['site_id'] === 'localhost',
    );
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

    Http::assertSent(
        fn ($request) => str_contains($request->url(), '/api/v1/stats/aggregate')
            && $request['metrics'] === 'visitors,pageviews,bounce_rate,visit_duration,events,visits'
            && $request['period'] === 'custom'
            && $request['compare'] === 'previous_period'
            && str_contains((string) $request['date'], ','),
    );
});

it('returns timeseries', function () {
    useHttpSnapshot('stats/timeseries');

    $metrics = plausible()
        ->stats()
        ->timeseries(TimeseriesRequest::make(
            site: 'localhost'
        ));

    expect($metrics)->toBeInstanceOf(Response::class);

    Http::assertSent(
        fn ($request) => str_contains($request->url(), '/api/v1/stats/timeseries')
            && $request['interval'] === 'date'
            && $request['period'] === '30d',
    );
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

    Http::assertSent(
        fn ($request) => str_contains($request->url(), '/api/v1/stats/breakdown')
            && $request['property'] === 'event:page'
            && $request['limit'] === 100,
    );
});
