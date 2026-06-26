<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use RoundlyConsulting\Plausible\Enums\Metric;
use RoundlyConsulting\Plausible\Requests\Stats\AggregateRequest;
use RoundlyConsulting\Plausible\Requests\Stats\BreakdownRequest;
use RoundlyConsulting\Plausible\Requests\Stats\TimeseriesRequest;
use RoundlyConsulting\Plausible\Results\AggregateResult;
use RoundlyConsulting\Plausible\Results\BreakdownResult;
use RoundlyConsulting\Plausible\Results\QueryResult;
use RoundlyConsulting\Plausible\Results\TimeseriesResult;

it('parses an aggregate result with typed accessors', function () {
    useHttpSnapshot('stats/aggregate');

    $result = plausible()->stats()->aggregateResult(AggregateRequest::make(
        site: 'localhost',
        metrics: [Metric::Visitors, Metric::Pageviews],
    ));

    expect($result->metric(Metric::Visitors))->toBe(2001)
        ->and($result->change(Metric::Pageviews))->toBe(-38)
        ->and($result->has(Metric::Visitors))->toBeTrue()
        ->and($result->has('nonexistent'))->toBeFalse()
        ->and($result->metric('nonexistent'))->toBe(0)
        ->and($result->change('nonexistent'))->toBeNull()
        ->and($result->response()->ok())->toBeTrue();
});

it('parses a timeseries result into points', function () {
    useHttpSnapshot('stats/timeseries');

    $result = plausible()->stats()->timeseriesResult(TimeseriesRequest::make(site: 'localhost'));

    expect($result->points())->toHaveCount(31)
        ->and($result->collect())->toHaveCount(31)
        ->and($result->points()[29]->metric(Metric::Visitors))->toBe(2)
        ->and($result->points()[29]->date->format('Y-m-d'))->toBe('2023-02-01')
        ->and($result->response()->ok())->toBeTrue();
});

it('parses a breakdown result into rows', function () {
    useHttpSnapshot('stats/breakdown');

    $result = plausible()->stats()->breakdownResult(BreakdownRequest::make(
        site: 'localhost',
        property: 'event:page',
    ));

    expect($result->rows())->toHaveCount(2)
        ->and($result->rows()[0]->metric(Metric::Visitors))->toBe(1)
        ->and($result->rows()[0]->metric('page'))->toBe(0)
        ->and($result->rows()[0]->get('missing'))->toBeNull()
        ->and($result->response()->ok())->toBeTrue();
});

it('handles a response with no results gracefully', function () {
    Http::fake(['*' => Http::response(['unexpected' => true], 200)]);

    $aggregate = AggregateResult::fromResponse(Http::get('http://localhost/x'));

    expect($aggregate->results)->toBe([])
        ->and($aggregate->metric(Metric::Visitors))->toBe(0);

    $query = QueryResult::fromResponse(Http::get('http://localhost/y'));

    expect($query->rows())->toBe([])
        ->and($query->meta())->toBe([])
        ->and($query->query())->toBe([]);
});

it('skips non-array rows when parsing results', function () {
    Http::fake(['*' => Http::response([
        'results' => ['not-an-array', ['date' => '2023-01-01', 'visitors' => 5]],
    ], 200)]);

    $timeseries = TimeseriesResult::fromResponse(Http::get('http://localhost/a'));
    expect($timeseries->points())->toHaveCount(1);

    Http::fake(['*' => Http::response([
        'results' => ['bad', ['page' => '/', 'visitors' => 1]],
    ], 200)]);

    $breakdown = BreakdownResult::fromResponse(Http::get('http://localhost/b'));
    expect($breakdown->rows())->toHaveCount(1);

    Http::fake(['*' => Http::response([
        'results' => ['bad', ['metrics' => [1], 'dimensions' => ['x']]],
    ], 200)]);

    $query = QueryResult::fromResponse(Http::get('http://localhost/c'));
    expect($query->rows())->toHaveCount(1);
});

it('defaults query row accessors when out of range', function () {
    Http::fake(['*' => Http::response([
        'results' => [['metrics' => 'bad', 'dimensions' => 'bad']],
    ], 200)]);

    $query = QueryResult::fromResponse(Http::get('http://localhost/z'));

    expect($query->rows()[0]->metric(5))->toBe(0)
        ->and($query->rows()[0]->dimension(5))->toBeNull();
});
