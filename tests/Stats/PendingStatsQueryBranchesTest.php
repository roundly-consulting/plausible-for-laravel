<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use RoundlyConsulting\Plausible\Enums\Metric;
use RoundlyConsulting\Plausible\Enums\Period;
use RoundlyConsulting\Plausible\Enums\Property;

it('applies a string filter to an aggregate query', function () {
    useHttpSnapshot('stats/aggregate');

    plausible()->stats()
        ->for('localhost')
        ->metrics(Metric::Visitors)
        ->filter('visit:browser==Chrome')
        ->aggregate();

    Http::assertSent(fn ($request) => $request['filters'] === 'visit:browser==Chrome');
});

it('overrides the site on an existing builder via for', function () {
    useHttpSnapshot('stats/aggregate');

    plausible()->stats()->query()->for('override.test')->metrics(Metric::Visitors)->aggregate();

    Http::assertSent(fn ($request) => $request['site_id'] === 'override.test');
});

it('exposes breakdown rows and query response as collections', function () {
    useHttpSnapshot('stats/breakdown');

    $breakdown = plausible()->stats()->for('localhost')->breakdown(Property::EventPage);
    expect($breakdown->collect())->toHaveCount(2);

    useHttpSnapshot('stats/query');

    $query = plausible()->stats()->for('localhost')->metrics(Metric::Visitors)->run();
    expect($query->response()->ok())->toBeTrue();
});

it('uses a relative date range for v2 when a period is set', function () {
    useHttpSnapshot('stats/query');

    plausible()->stats()
        ->for('localhost')
        ->metrics(Metric::Visitors)
        ->period(Period::SevenDays)
        ->run();

    Http::assertSent(fn ($request) => $request->data()['date_range'] === '7d');
});

it('falls back to 30d for v2 when only a custom period is set without dates', function () {
    useHttpSnapshot('stats/query');

    plausible()->stats()
        ->for('localhost')
        ->metrics(Metric::Visitors)
        ->period(Period::Custom)
        ->run();

    Http::assertSent(fn ($request) => $request->data()['date_range'] === '30d');
});
