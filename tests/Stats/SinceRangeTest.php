<?php

declare(strict_types=1);

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use RoundlyConsulting\Plausible\Enums\Period;
use RoundlyConsulting\Plausible\Requests\Stats\AggregateRequest;
use RoundlyConsulting\Plausible\Requests\Stats\BreakdownRequest;
use RoundlyConsulting\Plausible\Requests\Stats\TimeseriesRequest;

beforeEach(function () {
    Carbon::setTestNow('2024-03-15 14:30:00');
});

afterEach(function () {
    Carbon::setTestNow();
});

it('sends since() to v2 as a [start, today] date range', function () {
    useHttpSnapshot('stats/query');

    plausible()->stats()->for('localhost')->since(Carbon::parse('2024-01-01'))->run();

    Http::assertSent(fn ($request) => $request->data()['date_range'] === ['2024-01-01', '2024-03-15']);
});

it('sends since() to v1 as a custom start,today range', function () {
    useHttpSnapshot('stats/aggregate');

    plausible()->stats()->for('localhost')->since(Carbon::parse('2024-01-01'))->aggregate();

    Http::assertSent(fn ($request) => $request['period'] === 'custom'
        && $request['date'] === '2024-01-01,2024-03-15');
});

it('closes an open-ended custom range at today on every v1 request', function (string $class) {
    $request = match ($class) {
        AggregateRequest::class => AggregateRequest::make(site: 's', period: Period::Custom, start: Carbon::parse('2024-02-01')),
        TimeseriesRequest::class => TimeseriesRequest::make(site: 's', period: 'custom', start: Carbon::parse('2024-02-01')),
        BreakdownRequest::class => BreakdownRequest::make(site: 's', property: 'visit:source', period: 'custom', start: Carbon::parse('2024-02-01')),
    };

    expect($request->toQuery()->toArray())->toHaveKey('date', '2024-02-01,2024-03-15');
})->with([AggregateRequest::class, TimeseriesRequest::class, BreakdownRequest::class]);

it('keeps a single anchor date for a non-custom v1 period', function () {
    $query = AggregateRequest::make(site: 's', period: Period::Month, start: Carbon::parse('2024-01-01'))
        ->toQuery()->toArray();

    expect($query)->toHaveKey('date', '2024-01-01')->toHaveKey('period', 'month');
});

it('caches a since() query under its resolved range', function () {
    useHttpSnapshot('stats/aggregate');

    plausible()->stats()->for('localhost')->since(Carbon::parse('2024-01-01'))->cache(172800)->aggregate();
    Carbon::setTestNow('2024-03-16 09:00:00');
    plausible()->stats()->for('localhost')->since(Carbon::parse('2024-01-01'))->cache(172800)->aggregate();

    Http::assertSentCount(2);
});
