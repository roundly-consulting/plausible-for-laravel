<?php

declare(strict_types=1);

use Illuminate\Support\Carbon;
use RoundlyConsulting\Plausible\Requests\Query;
use RoundlyConsulting\Plausible\Requests\Stats\BreakdownRequest;

it('creates instance using make static method', function () {
    $request = BreakdownRequest::make('testing.site', 'test');

    expect($request)
        ->toBeInstanceOf(BreakdownRequest::class)
        ->site->toBe('testing.site')
        ->property->toBe('test');
});

it('checks whether any filter is applied', function () {
    $request = new BreakdownRequest(
        site: 'testing.tld',
        property: 'test',
        filters: ['visit:browser==Firefox'],
    );

    expect($request->hasFilters())->toBeTrue();

    $request = new BreakdownRequest(
        site: 'testing.tld',
        property: 'test',
    );

    expect($request->hasFilters())->toBeFalse();
});

it('checks custom date points', function () {
    $request = new BreakdownRequest(
        site: 'testing.tld',
        property: 'test',
    );

    expect($request->usesCustomStartPoint())
        ->toBeFalse()
        ->and($request->usesCustomEndPoint())
        ->toBeFalse();

    $request = new BreakdownRequest(
        site: 'testing.tld',
        property: 'test',
        start: now(),
    );

    expect($request->usesCustomStartPoint())
        ->toBeTrue()
        ->and($request->usesCustomEndPoint())
        ->toBeFalse();

    $request = new BreakdownRequest(
        site: 'testing.tld',
        property: 'test',
        end: now(),
    );

    expect($request->usesCustomStartPoint())
        ->toBeFalse()
        ->and($request->usesCustomEndPoint())
        ->toBeTrue();

    $request = new BreakdownRequest(
        site: 'testing.tld',
        property: 'test',
        start: now(),
        end: now(),
    );

    expect($request->usesCustomStartPoint())
        ->toBeTrue()
        ->and($request->usesCustomEndPoint())
        ->toBeTrue();
});

it('returns query instance', function () {
    $request = new BreakdownRequest(
        site: 'testing.tld',
        property: 'test',
        metrics: ['visitors', 'pageviews'],
        filters: ['visit:browser==Firefox', 'visit:country==FR'],
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
            'interval' => 'date',
            'property' => 'test',
            'limit' => 100,
            'page' => 1,
            'filters' => 'visit:browser==Firefox;visit:country==FR',
            'date' => '2023-02-01,2023-02-02',
        ]);

    $request = new BreakdownRequest(
        site: 'testing.tld',
        property: 'test',
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
            'property' => 'test',
            'limit' => 100,
            'page' => 1,
            'date' => '2023-02-01',
        ]);
});
