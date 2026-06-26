<?php

declare(strict_types=1);

use PHPUnit\Framework\ExpectationFailedException;
use RoundlyConsulting\Plausible\Enums\Property;
use RoundlyConsulting\Plausible\Facades\Plausible;
use RoundlyConsulting\Plausible\Requests\Events\CaptureEventRequest;
use RoundlyConsulting\Plausible\Requests\Stats\QueryRequest;

it('supplies default config when none is set', function () {
    config()->set('plausible.url', null);
    config()->set('plausible.key', null);

    $fake = Plausible::fake();

    expect($fake)->not->toBeNull()
        ->and(config('plausible.url'))->toBe('https://plausible.test')
        ->and(config('plausible.key'))->toBe('fake-key');

    Plausible::events()
        ->setVisitor('Agent/1.0', '1.2.3.4')
        ->capture(CaptureEventRequest::make(site: 'localhost', url: 'https://localhost/x'));

    Plausible::assertEventCaptured();
});

it('records every stats endpoint through the fake', function () {
    Plausible::fake();

    Plausible::stats()->for('localhost')->realtime();
    Plausible::stats()->for('localhost')->timeseries();
    Plausible::stats()->for('localhost')->property(Property::EventPage)->breakdown();
    Plausible::stats()->runQuery(QueryRequest::make(site: 'localhost', metrics: ['visitors']));

    Plausible::assertQueried(fn ($request, string $type) => $type === 'realtime');
    Plausible::assertQueried(fn ($request, string $type) => $type === 'timeseries');
    Plausible::assertQueried(fn ($request, string $type) => $type === 'breakdown');
    Plausible::assertQueried(fn ($request, string $type) => $type === 'query');
});

it('fails assertEventNotCaptured when a matching event exists', function () {
    Plausible::fake();

    Plausible::events()
        ->setVisitor('Agent/1.0', '1.2.3.4')
        ->capture(CaptureEventRequest::make(site: 'localhost', url: 'https://localhost/x', name: 'signup'));

    Plausible::assertEventNotCaptured(fn (CaptureEventRequest $event) => $event->name === 'signup');
})->throws(ExpectationFailedException::class);

it('passes assertEventNotCaptured when no event matches', function () {
    Plausible::fake();

    Plausible::events()
        ->setVisitor('Agent/1.0', '1.2.3.4')
        ->capture(CaptureEventRequest::make(site: 'localhost', url: 'https://localhost/x', name: 'signup'));

    Plausible::assertEventNotCaptured(fn (CaptureEventRequest $event) => $event->name === 'other');
});
