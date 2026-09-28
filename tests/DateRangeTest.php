<?php

declare(strict_types=1);

use Illuminate\Support\Carbon;
use RoundlyConsulting\Plausible\Requests\Stats\AggregateRequest;

it('formats a single anchor date for a non-custom period', function () {
    $query = AggregateRequest::make(
        site: 'localhost',
        period: 'day',
        start: Carbon::parse('2023-02-01'),
    )->toQuery()->toArray();

    expect($query)->toHaveKey('date', '2023-02-01');
});

it('formats a start and end range', function () {
    $query = AggregateRequest::make(
        site: 'localhost',
        period: 'custom',
        start: Carbon::parse('2023-02-01'),
        end: Carbon::parse('2023-02-28'),
    )->toQuery()->toArray();

    expect($query)->toHaveKey('date', '2023-02-01,2023-02-28');
});

it('omits the date when only an end is given', function () {
    $request = AggregateRequest::make(
        site: 'localhost',
        end: Carbon::parse('2023-02-28'),
    );

    // Only an end without a start does not trigger a custom range.
    expect($request->usesCustomStartPoint())->toBeFalse()
        ->and($request->toQuery()->toArray())->not->toHaveKey('date');
});
