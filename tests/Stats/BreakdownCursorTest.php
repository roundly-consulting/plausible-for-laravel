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

it('leaves the builder on its own page after the cursor', function (int $page) {
    Http::fake(['*' => Http::sequence()
        ->push(['results' => [['page' => '/a', 'visitors' => 1]]], 200)
        ->push(['results' => []], 200)
        ->push(['results' => [['page' => '/a', 'visitors' => 1]]], 200)]);

    $query = plausible()->stats()->for('localhost')->perPage(1)->page($page);

    iterator_to_array($query->cursor(Property::EventPage));
    $query->breakdown(Property::EventPage);

    $sent = Http::recorded();

    expect($sent)->toHaveCount(3)
        ->and($sent[0][0]['page'])->toBe(1)
        ->and($sent[1][0]['page'])->toBe(2)
        ->and($sent[2][0]['page'])->toBe($page);
})->with(['the default page' => [1], 'an explicit page' => [3]]);

it('leaves the builder on its own page after each() stops early', function () {
    Http::fake(['*' => Http::response(['results' => [['page' => '/a', 'visitors' => 1]]], 200)]);

    $query = plausible()->stats()->for('localhost')->perPage(1);

    $calls = 0;
    $query->each(function () use (&$calls) {
        return ++$calls < 2;
    }, Property::EventPage);
    $query->breakdown(Property::EventPage);

    expect(Http::recorded()->last()[0]['page'])->toBe(1);
});
