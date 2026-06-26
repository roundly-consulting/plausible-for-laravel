<?php

declare(strict_types=1);

use PHPUnit\Framework\ExpectationFailedException;
use RoundlyConsulting\Plausible\Enums\Metric;
use RoundlyConsulting\Plausible\Facades\Plausible;
use RoundlyConsulting\Plausible\Requests\Events\CaptureEventRequest;
use RoundlyConsulting\Plausible\Requests\Sites\CreateSiteRequest;
use RoundlyConsulting\Plausible\Requests\Sites\UpdateSiteRequest;
use RoundlyConsulting\Plausible\Testing\PlausibleFake;

it('swaps the manager for a recording fake', function () {
    $fake = Plausible::fake();

    expect($fake)->toBeInstanceOf(PlausibleFake::class)
        ->and(plausible())->toBe($fake);
});

it('records captured events and asserts on them', function () {
    Plausible::fake();

    Plausible::events()
        ->setVisitor('Agent/1.0', '1.2.3.4')
        ->capture(CaptureEventRequest::make(site: 'localhost', url: 'https://localhost/x', name: 'signup'));

    Plausible::assertEventCaptured(fn (CaptureEventRequest $event) => $event->name === 'signup');
    Plausible::assertEventCapturedCount(1);
});

it('records queued events without dispatching a job', function () {
    Plausible::fake();

    Plausible::events()
        ->setVisitor('Agent/1.0', '1.2.3.4')
        ->captureQueued(CaptureEventRequest::make(site: 'localhost', url: 'https://localhost/x', name: 'purchase'));

    Plausible::assertEventCaptured(fn (CaptureEventRequest $event) => $event->name === 'purchase');
});

it('fails when an expected event was not captured', function () {
    Plausible::fake();

    Plausible::assertEventCaptured(fn (CaptureEventRequest $event) => $event->name === 'missing');
})->throws(ExpectationFailedException::class);

it('asserts nothing was captured', function () {
    Plausible::fake();

    Plausible::assertNothingCaptured();
    Plausible::assertEventNotCaptured();
});

it('records stats queries and asserts on them', function () {
    Plausible::fake();

    Plausible::stats()->for('localhost')->metrics(Metric::Visitors)->aggregate();

    Plausible::assertQueried(fn ($request, string $type) => $type === 'aggregate');
    Plausible::assertQueried();
});

it('asserts no queries were made', function () {
    Plausible::fake();

    Plausible::assertNothingQueried();
});

it('records site operations and asserts on them', function () {
    Plausible::fake();

    Plausible::sites()->create(CreateSiteRequest::make(domain: 'new.test'));
    Plausible::sites()->delete('old.test');
    Plausible::sites()->update(UpdateSiteRequest::make(site: 'lorem.test', domain: 'dolor.test'));

    Plausible::assertSiteCreated('new.test');
    Plausible::assertSiteCreated();
    Plausible::assertSiteDeleted('old.test');
    Plausible::assertSiteUpdated('lorem.test');
});

it('fails when an expected site was not created', function () {
    Plausible::fake();

    Plausible::assertSiteCreated('never.test');
})->throws(ExpectationFailedException::class);
