<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Bus;
use RoundlyConsulting\Plausible\Facades\Plausible;
use RoundlyConsulting\Plausible\Requests\Events\CaptureEventRequest;
use RoundlyConsulting\Plausible\Requests\Sites\CreateGoalRequest;

it('records and asserts goals through the fake', function () {
    Plausible::fake();

    Plausible::goals()->create(CreateGoalRequest::event('a.test', 'Signup'));
    Plausible::goals()->delete('a.test', 7);

    Plausible::assertGoalCreated();
    Plausible::assertGoalCreated('a.test');
    Plausible::assertGoalDeleted('a.test', 7);
});

it('records and asserts shared links through the fake', function () {
    Plausible::fake();

    Plausible::sharedLinks()->create('a.test', 'Dashboard');

    Plausible::assertSharedLinkCreated();
    Plausible::assertSharedLinkCreated('a.test');
});

it('records queued batch captures through the fake', function () {
    Bus::fake();
    Plausible::fake();

    Plausible::events()->setVisitor('Agent/1.0', '1.2.3.4')->captureManyQueued([
        CaptureEventRequest::make(site: 'a.test', url: 'https://a.test/1'),
        CaptureEventRequest::make(site: 'a.test', url: 'https://a.test/2'),
    ]);

    Plausible::assertEventCapturedCount(2);
    Bus::assertBatched(fn ($batch) => $batch->jobs->count() === 2);
});

it('reports health through the fake', function () {
    Plausible::fake();

    expect(Plausible::ping())->toBeTrue();
});
