<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use RoundlyConsulting\Plausible\Enums\Metric;
use RoundlyConsulting\Plausible\Enums\Property;
use RoundlyConsulting\Plausible\Results\AggregateResult;

it('caches aggregate results by query signature', function () {
    useHttpSnapshot('stats/aggregate');

    $first = plausible()->stats()->for('localhost')->metrics(Metric::Visitors)->cache(60)->aggregate();
    $second = plausible()->stats()->for('localhost')->metrics(Metric::Visitors)->cache(60)->aggregate();

    expect($first)->toBeInstanceOf(AggregateResult::class)
        ->and($second->metric(Metric::Visitors))->toBe($first->metric(Metric::Visitors))
        ->and($second->response()->json('results'))->not->toBeNull();

    Http::assertSentCount(1);
});

it('uses a different cache entry for a different signature', function () {
    useHttpSnapshot('stats/aggregate');

    plausible()->stats()->for('localhost')->metrics(Metric::Visitors)->cache(60)->aggregate();
    plausible()->stats()->for('other.test')->metrics(Metric::Visitors)->cache(60)->aggregate();

    Http::assertSentCount(2);
});

it('does not cache when no ttl is set', function () {
    useHttpSnapshot('stats/aggregate');

    plausible()->stats()->for('localhost')->metrics(Metric::Visitors)->aggregate();
    plausible()->stats()->for('localhost')->metrics(Metric::Visitors)->aggregate();

    Http::assertSentCount(2);
});

it('caches timeseries, breakdown, realtime and v2 query', function () {
    Http::fake([
        '*timeseries*' => snapshot('stats/timeseries'),
        '*breakdown*' => snapshot('stats/breakdown'),
        '*realtime*' => snapshot('stats/realtime'),
        '*api/v2/query*' => snapshot('stats/query'),
    ]);

    plausible()->stats()->for('localhost')->cache(60)->timeseries();
    plausible()->stats()->for('localhost')->cache(60)->timeseries();

    plausible()->stats()->for('localhost')->cache(60)->breakdown(Property::EventPage);
    plausible()->stats()->for('localhost')->cache(60)->breakdown(Property::EventPage);

    expect(plausible()->stats()->for('localhost')->cache(60)->realtime())->toBe(10);
    plausible()->stats()->for('localhost')->cache(60)->realtime();

    plausible()->stats()->for('localhost')->metrics(Metric::Visitors)->cache(60)->run();
    plausible()->stats()->for('localhost')->metrics(Metric::Visitors)->cache(60)->run();

    Http::assertSentCount(4);
});

it('accepts a DateTimeInterface ttl and a store override', function () {
    useHttpSnapshot('stats/aggregate');

    plausible()->stats()->for('localhost')->cache(now()->addMinutes(5), 'array')->aggregate();
    plausible()->stats()->for('localhost')->cache(now()->addMinutes(5), 'array')->aggregate();

    Http::assertSentCount(1);
});
