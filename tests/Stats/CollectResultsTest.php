<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use RoundlyConsulting\Plausible\Enums\Metric;
use RoundlyConsulting\Plausible\Enums\Property;
use RoundlyConsulting\Plausible\Results\BreakdownRow;
use RoundlyConsulting\Plausible\Results\QueryRow;
use RoundlyConsulting\Plausible\Results\TimeseriesPoint;

it('collects aggregate metrics into a keyed collection', function () {
    useHttpSnapshot('stats/aggregate');

    $collection = plausible()->stats()->for('localhost')->metrics(Metric::Visitors)->aggregate()->collect();

    expect($collection)->toBeInstanceOf(Collection::class)
        ->and($collection->get('visitors'))->toBe(2001)
        ->and($collection->get('pageviews'))->toBe(4103);
});

it('collects breakdown rows into a collection', function () {
    useHttpSnapshot('stats/breakdown');

    $collection = plausible()->stats()->for('localhost')->breakdown(Property::EventPage)->collect();

    expect($collection)->toBeInstanceOf(Collection::class)
        ->and($collection)->toHaveCount(2)
        ->and($collection->first())->toBeInstanceOf(BreakdownRow::class);
});

it('collects timeseries points into a collection', function () {
    useHttpSnapshot('stats/timeseries');

    $collection = plausible()->stats()->for('localhost')->timeseries()->collect();

    expect($collection)->toBeInstanceOf(Collection::class)
        ->and($collection->first())->toBeInstanceOf(TimeseriesPoint::class);
});

it('collects v2 query rows into a collection', function () {
    useHttpSnapshot('stats/query');

    $collection = plausible()->stats()->for('localhost')->metrics(Metric::Visitors)->run()->collect();

    expect($collection)->toBeInstanceOf(Collection::class)
        ->and($collection->first())->toBeInstanceOf(QueryRow::class);
});
