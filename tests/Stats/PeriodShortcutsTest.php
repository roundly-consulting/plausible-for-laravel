<?php

declare(strict_types=1);

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use RoundlyConsulting\Plausible\Enums\Metric;

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
