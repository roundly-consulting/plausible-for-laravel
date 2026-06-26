<?php

declare(strict_types=1);

use RoundlyConsulting\Plausible\Enums\Dimension;
use RoundlyConsulting\Plausible\Enums\Metric;
use RoundlyConsulting\Plausible\Enums\Period;
use RoundlyConsulting\Plausible\Requests\Stats\QueryRequest;

it('builds a minimal body with a relative date range', function () {
    $body = QueryRequest::make(
        site: 'localhost',
        metrics: [Metric::Visitors],
    )->toBody();

    expect($body)->toBe([
        'site_id' => 'localhost',
        'metrics' => ['visitors'],
        'date_range' => '30d',
    ]);
});

it('normalises an enum date range', function () {
    $body = QueryRequest::make(
        site: 'localhost',
        metrics: ['visitors'],
        dateRange: Period::SevenDays,
    )->toBody();

    expect($body['date_range'])->toBe('7d');
});

it('includes all optional sections when provided', function () {
    $body = QueryRequest::make(
        site: 'localhost',
        metrics: [Metric::Visitors],
        dateRange: ['2024-01-01', '2024-01-31'],
        dimensions: [Dimension::EventPage],
        filters: [['is', 'visit:browser', ['Chrome']]],
        orderBy: [['visitors', 'desc']],
        include: ['time_labels' => true],
        limit: 20,
        offset: 40,
    )->toBody();

    expect($body)->toBe([
        'site_id' => 'localhost',
        'metrics' => ['visitors'],
        'date_range' => ['2024-01-01', '2024-01-31'],
        'dimensions' => ['event:page'],
        'filters' => [['is', 'visit:browser', ['Chrome']]],
        'order_by' => [['visitors', 'desc']],
        'include' => ['time_labels' => true],
        'pagination' => ['limit' => 20, 'offset' => 40],
    ]);
});

it('runs a v2 query through the repository directly', function () {
    useHttpSnapshot('stats/query');

    $result = plausible()->stats()->runQuery(QueryRequest::make(
        site: 'localhost',
        metrics: [Metric::Visitors],
    ));

    expect($result->meta())->toHaveKey('imports_included', false)
        ->and($result->query())->toHaveKey('site_id', 'localhost')
        ->and($result->collect())->toHaveCount(2);
});
