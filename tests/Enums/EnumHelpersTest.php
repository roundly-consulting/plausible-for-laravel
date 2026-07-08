<?php

declare(strict_types=1);

use RoundlyConsulting\Plausible\Enums\Dimension;
use RoundlyConsulting\Plausible\Enums\FilterOperator;
use RoundlyConsulting\Plausible\Enums\GoalType;
use RoundlyConsulting\Plausible\Enums\Interval;
use RoundlyConsulting\Plausible\Enums\Metric;
use RoundlyConsulting\Plausible\Enums\Period;
use RoundlyConsulting\Plausible\Enums\Property;

it('exposes the enums Helpers surface on every query enum', function () {
    expect(Metric::values()->all())->toBe([
        'visitors', 'visits', 'pageviews', 'views_per_visit', 'bounce_rate',
        'visit_duration', 'events', 'scroll_depth', 'conversion_rate',
        'group_conversion_rate', 'total_revenue', 'average_revenue',
        'time_on_page', 'exit_rate', 'percentage',
    ]);

    expect(Metric::validationRule())->toBe(
        'in:visitors,visits,pageviews,views_per_visit,bounce_rate,visit_duration,'.
        'events,scroll_depth,conversion_rate,group_conversion_rate,total_revenue,'.
        'average_revenue,time_on_page,exit_rate,percentage',
    );

    expect(Metric::hasValue('event:name'))->toBeFalse()
        ->and(Metric::hasValue('visitors'))->toBeTrue()
        ->and(Metric::tryFromName('Nope'))->toBeNull()
        ->and(Metric::fromName('Visitors'))->toBe(Metric::Visitors)
        ->and(Metric::count())->toBe(15);
});

it('keeps clean value-based labels on the four unabbreviated enums', function () {
    expect(Metric::ViewsPerVisit->readable())->toBe('Views Per Visit')
        ->and(Metric::Visitors->label())->toBe('Visitors')
        ->and(Interval::Date->readable())->toBe('Date')
        ->and(Interval::labels()->all())->toBe(['Minute', 'Hour', 'Date', 'Week', 'Month'])
        ->and(GoalType::fromName('Event'))->toBe(GoalType::Event)
        ->and(GoalType::Event->readable())->toBe('Event')
        ->and(FilterOperator::HasNotDone->readable())->toBe('Has Not Done')
        ->and(FilterOperator::IsNot->value)->toBe('is_not')
        ->and(FilterOperator::tryFromName('IsNot'))->toBe(FilterOperator::IsNot);
});

it('overrides Period labels from the case name while preserving wire values', function () {
    expect(Period::SevenDays->value)->toBe('7d')
        ->and(Period::SevenDays->readable())->toBe('Seven Days')
        ->and(Period::SixMonths->readable())->toBe('Six Months')
        ->and(Period::TwelveMonths->readable())->toBe('Twelve Months')
        ->and(Period::All->readable())->toBe('All')
        ->and(Period::values()->all())->toBe([
            'day', '7d', '30d', 'month', '6mo', '12mo', 'year', 'all', 'custom',
        ])
        ->and(Period::fromName('SixMonths'))->toBe(Period::SixMonths)
        ->and(Period::toOptions()->get('7d'))->toBe('Seven Days');
});

it('overrides Dimension labels from the case name while preserving wire values', function () {
    expect(Dimension::EventName->value)->toBe('event:name')
        ->and(Dimension::EventName->readable())->toBe('Event Name')
        ->and(Dimension::VisitUtmMedium->readable())->toBe('Visit Utm Medium')
        ->and(Dimension::TimeHour->readable())->toBe('Time Hour')
        ->and(Dimension::custom('plan'))->toBe('event:props:plan')
        ->and(Dimension::fromLabel('Event Name'))->toBe(Dimension::EventName)
        ->and(Dimension::options()->firstWhere('value', 'event:name')->label)->toBe('Event Name');
});

it('overrides Property labels from the case name while preserving wire values', function () {
    expect(Property::VisitUtmSource->value)->toBe('visit:utm_source')
        ->and(Property::VisitUtmSource->readable())->toBe('Visit Utm Source')
        ->and(Property::EventName->readable())->toBe('Event Name')
        ->and(Property::custom('plan'))->toBe('event:props:plan')
        ->and(Property::fromLabel('Visit Browser'))->toBe(Property::VisitBrowser)
        ->and(Property::hasValue('visit:source'))->toBeTrue()
        ->and(Property::hasValue('nope'))->toBeFalse();
});
