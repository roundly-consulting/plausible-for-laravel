<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use RoundlyConsulting\Plausible\Enums\Property;
use RoundlyConsulting\Plausible\Exceptions\MissingConfiguration;
use RoundlyConsulting\Plausible\Results\BreakdownRow;

it('walks every breakdown page through the cursor', function () {
    Http::fakeSequence()
        ->push(['results' => [['page' => '/a', 'visitors' => 1], ['page' => '/b', 'visitors' => 1]]], 200)
        ->push(['results' => [['page' => '/c', 'visitors' => 1]]], 200);

    $rows = iterator_to_array(
        plausible()->stats()->for('localhost')->perPage(2)->cursor(Property::EventPage),
    );

    expect($rows)->toHaveCount(3)
        ->and($rows[0])->toBeInstanceOf(BreakdownRow::class);

    Http::assertSentCount(2);
});

it('stops the cursor on an empty page', function () {
    Http::fakeSequence()
        ->push(['results' => [['page' => '/a', 'visitors' => 1]]], 200)
        ->push(['results' => []], 200);

    $rows = iterator_to_array(
        plausible()->stats()->for('localhost')->perPage(1)->cursor(Property::EventPage),
    );

    expect($rows)->toHaveCount(1);

    Http::assertSentCount(2);
});

it('iterates rows with each and stops early when the callback returns false', function () {
    useHttpSnapshot('stats/breakdown');

    $seen = [];

    plausible()->stats()->for('localhost')->each(function (BreakdownRow $row) use (&$seen) {
        $seen[] = $row->get('page');

        return false;
    }, Property::EventPage);

    expect($seen)->toBe(['/']);
});

it('uses the fluent property for the cursor when none is passed', function () {
    useHttpSnapshot('stats/breakdown');

    $rows = iterator_to_array(
        plausible()->stats()->for('localhost')->property(Property::EventPage)->cursor(),
    );

    expect($rows)->toHaveCount(2);
});

it('throws from the cursor when no property is available', function () {
    iterator_to_array(plausible()->stats()->for('localhost')->cursor());
})->throws(MissingConfiguration::class);
