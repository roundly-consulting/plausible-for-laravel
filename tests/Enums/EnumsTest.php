<?php

declare(strict_types=1);

use RoundlyConsulting\Plausible\Enums\Dimension;
use RoundlyConsulting\Plausible\Enums\FilterOperator;
use RoundlyConsulting\Plausible\Enums\Interval;
use RoundlyConsulting\Plausible\Enums\Metric;
use RoundlyConsulting\Plausible\Enums\Period;
use RoundlyConsulting\Plausible\Enums\Property;
use RoundlyConsulting\Plausible\Requests\Stats\AggregateRequest;
use RoundlyConsulting\Plausible\Support\Value;

it('backs metrics, periods and intervals with the api keys', function () {
    expect(Metric::Visitors->value)->toBe('visitors')
        ->and(Metric::BounceRate->value)->toBe('bounce_rate')
        ->and(Metric::ScrollDepth->value)->toBe('scroll_depth')
        ->and(Period::SevenDays->value)->toBe('7d')
        ->and(Period::Custom->value)->toBe('custom')
        ->and(Interval::Date->value)->toBe('date')
        ->and(Property::VisitSource->value)->toBe('visit:source')
        ->and(Dimension::TimeHour->value)->toBe('time:hour')
        ->and(FilterOperator::IsNot->value)->toBe('is_not');
});

it('builds custom property and dimension strings', function () {
    expect(Property::custom('plan'))->toBe('event:props:plan')
        ->and(Dimension::custom('plan'))->toBe('event:props:plan');
});

it('normalises strings and enums identically through Value', function () {
    expect(Value::of(Metric::Visitors))->toBe('visitors')
        ->and(Value::of('visitors'))->toBe('visitors')
        ->and(Value::list([Metric::Visitors, 'pageviews']))->toBe(['visitors', 'pageviews']);
});

it('produces identical queries for enum and string metrics', function () {
    $withEnums = AggregateRequest::make(
        site: 'localhost',
        metrics: [Metric::Visitors, Metric::Pageviews],
        period: Period::ThirtyDays,
    )->toQuery()->toArray();

    $withStrings = AggregateRequest::make(
        site: 'localhost',
        metrics: ['visitors', 'pageviews'],
        period: '30d',
    )->toQuery()->toArray();

    expect($withEnums)->toBe($withStrings);
});
