<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use RoundlyConsulting\Plausible\Enums\Dimension;
use RoundlyConsulting\Plausible\Enums\Interval;
use RoundlyConsulting\Plausible\Enums\Metric;
use RoundlyConsulting\Plausible\Enums\Period;
use RoundlyConsulting\Plausible\Enums\Property;
use RoundlyConsulting\Plausible\Exceptions\MissingConfiguration;
use RoundlyConsulting\Plausible\Results\AggregateResult;
use RoundlyConsulting\Plausible\Results\BreakdownResult;
use RoundlyConsulting\Plausible\Results\QueryResult;
use RoundlyConsulting\Plausible\Results\TimeseriesResult;

it('builds an aggregate query fluently and returns a typed result', function () {
    useHttpSnapshot('stats/aggregate');

    $result = plausible()->stats()
        ->for('localhost')
        ->metrics(Metric::Visitors, Metric::Pageviews)
        ->period(Period::ThirtyDays)
        ->compare()
        ->aggregate();

    expect($result)->toBeInstanceOf(AggregateResult::class)
        ->and($result->metric(Metric::Visitors))->toBe(2001)
        ->and($result->change(Metric::Visitors))->toBe(30);

    Http::assertSent(
        fn ($request) => str_contains($request->url(), '/api/v1/stats/aggregate')
            && $request['site_id'] === 'localhost'
            && $request['metrics'] === 'visitors,pageviews'
            && $request['compare'] === 'previous_period',
    );
});

it('sets period to custom when using between', function () {
    useHttpSnapshot('stats/timeseries');

    $result = plausible()->stats()
        ->for('localhost')
        ->metrics(Metric::Visitors)
        ->interval(Interval::Date)
        ->between(now()->subDays(7), now())
        ->timeseries();

    expect($result)->toBeInstanceOf(TimeseriesResult::class);

    Http::assertSent(
        fn ($request) => $request['period'] === 'custom'
            && str_contains((string) $request['date'], ','),
    );
});

it('sets period to custom when using since', function () {
    useHttpSnapshot('stats/aggregate');

    plausible()->stats()
        ->for('localhost')
        ->since(now()->subDays(3))
        ->aggregate();

    Http::assertSent(fn ($request) => $request['period'] === 'custom');
});

it('builds a breakdown query with a property argument', function () {
    useHttpSnapshot('stats/breakdown');

    $result = plausible()->stats()
        ->for('localhost')
        ->metrics(Metric::Visitors)
        ->page(2)
        ->perPage(50)
        ->breakdown(Property::EventPage);

    expect($result)->toBeInstanceOf(BreakdownResult::class)
        ->and($result->rows())->toHaveCount(2)
        ->and($result->rows()[0]->get('page'))->toBe('/');

    Http::assertSent(
        fn ($request) => $request['property'] === 'event:page'
            && $request['page'] === 2
            && $request['limit'] === 50,
    );
});

it('uses a fluent property when none is passed to breakdown', function () {
    useHttpSnapshot('stats/breakdown');

    plausible()->stats()->for('localhost')->property(Property::VisitSource)->breakdown();

    Http::assertSent(fn ($request) => $request['property'] === 'visit:source');
});

it('throws when breakdown has no property', function () {
    plausible()->stats()->for('localhost')->breakdown();
})->throws(MissingConfiguration::class);

it('returns realtime visitors through the builder', function () {
    useHttpSnapshot('stats/realtime');

    expect(plausible()->stats()->for('localhost')->realtime())->toBe(10);
});

it('falls back to the default site when none is given', function () {
    config()->set('plausible.site', 'configured.test');
    useHttpSnapshot('stats/aggregate');

    plausible()->stats()->query()->metrics(Metric::Visitors)->aggregate();

    Http::assertSent(fn ($request) => $request['site_id'] === 'configured.test');
});

it('throws when no site is available', function () {
    config()->set('plausible.site', null);

    plausible()->stats()->query()->aggregate();
})->throws(MissingConfiguration::class);

it('runs a v2 query through the builder', function () {
    useHttpSnapshot('stats/query');

    $result = plausible()->stats()
        ->for('localhost')
        ->metrics(Metric::Visitors, Metric::BounceRate)
        ->dimensions(Dimension::EventPage)
        ->orderBy(['visitors', 'desc'])
        ->include(['imports' => true])
        ->page(2)
        ->perPage(10)
        ->filter(['is', 'visit:browser', ['Chrome']])
        ->between(now()->subDays(7), now())
        ->run();

    expect($result)->toBeInstanceOf(QueryResult::class)
        ->and($result->rows())->toHaveCount(2)
        ->and($result->rows()[0]->metric(0))->toBe(100)
        ->and($result->rows()[0]->dimension(0))->toBe('/blog');

    Http::assertSent(function ($request) {
        $body = $request->data();

        return str_contains($request->url(), '/api/v2/query')
            && $body['site_id'] === 'localhost'
            && $body['metrics'] === ['visitors', 'bounce_rate']
            && $body['dimensions'] === ['event:page']
            && $body['order_by'] === [['visitors', 'desc']]
            && $body['include'] === ['imports' => true]
            && is_array($body['date_range'])
            && $body['filters'] === [['is', 'visit:browser', ['Chrome']]]
            && $body['pagination'] === ['limit' => 10, 'offset' => 10];
    });
});

// Plausible's v2 default page size is 10000, so a page of 100 must say so explicitly.
it('sends the page size with every v2 offset and with an explicit perPage', function (Closure $build, ?array $pagination) {
    useHttpSnapshot('stats/query');

    $build(plausible()->stats()->for('localhost')->metrics(Metric::Visitors))->run();

    Http::assertSent(fn ($request) => ($request->data()['pagination'] ?? null) === $pagination);
})->with([
    'page 2 at the default size' => [fn ($q) => $q->page(2), ['limit' => 100, 'offset' => 100]],
    'an explicit perPage(100)' => [fn ($q) => $q->perPage(100), ['limit' => 100]],
    'no paging at all' => [fn ($q) => $q, null],
]);

it('names the missing breakdown property, not the site', function (Closure $call) {
    Http::fake();

    try {
        $call(plausible()->stats()->for('given.test'));
        $this->fail('Expected a MissingConfiguration exception.');
    } catch (MissingConfiguration $e) {
        expect($e->getMessage())->toContain('property')
            ->not->toContain('No site given');
    }

    Http::assertNothingSent();
})->with([
    'breakdown()' => [fn ($q) => $q->breakdown()],
    'cursor()' => [fn ($q) => iterator_to_array($q->cursor())],
    'each()' => [fn ($q) => $q->each(fn () => null)],
]);
