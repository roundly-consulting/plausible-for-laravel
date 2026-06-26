<?php

declare(strict_types=1);

use Illuminate\Support\Carbon;
use RoundlyConsulting\Plausible\Requests\Query;
use RoundlyConsulting\Plausible\Requests\Stats\TimeseriesRequest;

it('creates instance using make static method', function () {
    $request = TimeseriesRequest::make('testing.site');

    expect($request)
        ->toBeInstanceOf(TimeseriesRequest::class)
        ->site->toBe('testing.site');
});

it('checks whether any filter is applied', function () {
    $request = new TimeseriesRequest(
        site: 'testing.tld',
        filters: ['visit:browser==Firefox'],
    );

    expect($request->hasFilters())->toBeTrue();

    $request = new TimeseriesRequest(
        site: 'testing.tld',
    );

    expect($request->hasFilters())->toBeFalse();
});

it('checks custom date points', function () {
    $request = new TimeseriesRequest(
        site: 'testing.tld',
    );

    expect($request->usesCustomStartPoint())
        ->toBeFalse()
        ->and($request->usesCustomEndPoint())
        ->toBeFalse();

    $request = new TimeseriesRequest(
        site: 'testing.tld',
        start: now(),
    );

    expect($request->usesCustomStartPoint())
        ->toBeTrue()
        ->and($request->usesCustomEndPoint())
        ->toBeFalse();

    $request = new TimeseriesRequest(
        site: 'testing.tld',
        end: now(),
    );

    expect($request->usesCustomStartPoint())
        ->toBeFalse()
        ->and($request->usesCustomEndPoint())
        ->toBeTrue();

    $request = new TimeseriesRequest(
        site: 'testing.tld',
        start: now(),
        end: now(),
    );

    expect($request->usesCustomStartPoint())
        ->toBeTrue()
        ->and($request->usesCustomEndPoint())
        ->toBeTrue();
});

it('returns query instance', function () {
    $request = new TimeseriesRequest(
        site: 'testing.tld',
        metrics: ['visitors', 'pageviews'],
        filters: ['visit:browser==Firefox', 'visit:country==FR'],
        interval: 'hour',
        period: '1d',
        start: Carbon::parse('2023-02-01'),
        end: Carbon::parse('2023-02-02'),
    );

    $query = $request->toQuery();

    expect($query)
        ->toBeInstanceOf(Query::class)
        ->toArray()
        ->toBe([
            'site_id' => 'testing.tld',
            'metrics' => 'visitors,pageviews',
            'period' => '1d',
            'interval' => 'hour',
            'filters' => 'visit:browser==Firefox;visit:country==FR',
            'date' => '2023-02-01,2023-02-02',
        ]);

    $request = new TimeseriesRequest(
        site: 'testing.tld',
        start: Carbon::parse('2023-02-01'),
    );

    $query = $request->toQuery();

    expect($query)
        ->toBeInstanceOf(Query::class)
        ->toArray()
        ->toBe([
            'site_id' => 'testing.tld',
            'metrics' => 'visitors',
            'period' => '30d',
            'interval' => 'date',
            'date' => '2023-02-01',
        ]);
});
