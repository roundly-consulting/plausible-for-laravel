<?php

declare(strict_types=1);

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use RoundlyConsulting\Plausible\Enums\Metric;
use RoundlyConsulting\Plausible\Enums\Period;
use RoundlyConsulting\Plausible\Enums\Property;
use RoundlyConsulting\Plausible\Exceptions\PlausibleException;
use RoundlyConsulting\Plausible\Requests\Stats\AggregateRequest;

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-06-15 12:00:00'));
    useHttpSnapshot('stats/aggregate');
});

afterEach(function () {
    Carbon::setTestNow();
});

it('maps relative period shortcuts to the right period', function (string $method, string $period) {
    plausible()->stats()->for('localhost')->metrics(Metric::Visitors)->{$method}()->aggregate();

    Http::assertSent(fn ($request) => $request['period'] === $period);
})->with([
    ['today', 'day'],
    ['last7Days', '7d'],
    ['last30Days', '30d'],
    ['last6Months', '6mo'],
    ['last12Months', '12mo'],
    ['thisMonth', 'month'],
]);

// The v1 endpoints accept only day/7d/30d/month/6mo/12mo/custom: `year` is sent as a custom
// range from 1 January through today, and `all` has no v1 form at all.
it('sends thisYear to the v1 endpoints as a custom range from 1 January through today', function (string $terminal) {
    $query = plausible()->stats()->for('localhost')->metrics(Metric::Visitors)->thisYear();

    $terminal === 'breakdown' ? $query->breakdown(Property::VisitSource) : $query->{$terminal}();

    Http::assertSent(fn ($request) => $request['period'] === 'custom'
        && $request['date'] === '2026-01-01,2026-06-15');
})->with(['aggregate', 'timeseries', 'breakdown']);

it('sends a year period on a v1 request object as a custom range', function () {
    plausible()->stats()->aggregate(AggregateRequest::make(site: 'localhost', period: 'year'));

    Http::assertSent(fn ($request) => $request['period'] === 'custom'
        && $request['date'] === '2026-01-01,2026-06-15');
});

it('refuses allTime on the v1 endpoints and points to run()', function (string $terminal) {
    $query = plausible()->stats()->for('localhost')->metrics(Metric::Visitors)->allTime();

    expect(fn () => $terminal === 'breakdown' ? $query->breakdown(Property::VisitSource) : $query->{$terminal}())
        ->toThrow(PlausibleException::class, 'run()');

    Http::assertNothingSent();
})->with(['aggregate', 'timeseries', 'breakdown']);

it('refuses an all period on a v1 request object', function () {
    expect(fn () => AggregateRequest::make(site: 'localhost', period: Period::All))
        ->toThrow(PlausibleException::class, 'run()');
});

it('keeps year and all on the v2 run() terminal', function (string $method, string $range) {
    useHttpSnapshot('stats/query');

    plausible()->stats()->for('localhost')->metrics(Metric::Visitors)->{$method}()->run();

    Http::assertSent(fn ($request) => $request['date_range'] === $range);
})->with([
    ['thisYear', 'year'],
    ['allTime', 'all'],
]);

it('maps lastMonth to a custom date range', function () {
    plausible()->stats()->for('localhost')->metrics(Metric::Visitors)->lastMonth()->aggregate();

    Http::assertSent(
        fn ($request) => $request['period'] === 'custom'
            && $request['date'] === '2026-05-01,2026-05-31',
    );
});

it('maps yesterday to a single-day custom range', function () {
    plausible()->stats()->for('localhost')->metrics(Metric::Visitors)->yesterday()->aggregate();

    Http::assertSent(
        fn ($request) => $request['period'] === 'custom'
            && $request['date'] === '2026-06-14,2026-06-14',
    );
});
