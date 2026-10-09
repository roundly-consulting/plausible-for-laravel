<?php

declare(strict_types=1);

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use RoundlyConsulting\Plausible\Enums\Interval;
use RoundlyConsulting\Plausible\Exceptions\PlausibleException;
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
        interval: 'month',
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
            'interval' => 'month',
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

// Plausible's v1 timeseries accepts only `date` (alias `day`) and `month`; anything else is a 400.
it('refuses the intervals the v1 timeseries endpoint rejects', function (Interval|string $interval) {
    Http::fake();

    expect(fn () => plausible()->stats()->for('localhost')->interval($interval)->timeseries())
        ->toThrow(PlausibleException::class, 'run()');

    Http::assertNothingSent();
})->with([Interval::Minute, Interval::Hour, Interval::Week, 'hour']);

it('points hourly and weekly buckets to the v2 time dimensions', function () {
    expect(fn () => TimeseriesRequest::make('localhost', interval: Interval::Hour))
        ->toThrow(PlausibleException::class, 'Dimension::TimeHour');
});

it('sends the intervals the v1 timeseries endpoint accepts', function (Interval|string $interval, string $wire) {
    useHttpSnapshot('stats/timeseries');

    plausible()->stats()->for('localhost')->interval($interval)->timeseries();

    Http::assertSent(fn ($request) => $request['interval'] === $wire);
})->with([
    [Interval::Date, 'date'],
    [Interval::Month, 'month'],
    ['day', 'day'],
]);
