<?php

declare(strict_types=1);

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use RoundlyConsulting\Plausible\Exceptions\UniqueVisitorIdentifierRequired;
use RoundlyConsulting\Plausible\Facades\Plausible;
use RoundlyConsulting\Plausible\Jobs\CaptureEventJob;
use RoundlyConsulting\Plausible\Requests\Events\CaptureEventRequest;

/*
 * The fake must refuse what the real client refuses: a queued capture without a visitor
 * throws in production, so it has to throw under Plausible::fake() too.
 */

it('refuses a queued capture without a visitor, like the real client', function () {
    Plausible::fake();

    expect(fn () => Plausible::events()->captureQueued(CaptureEventRequest::make(site: 'a.test', url: 'https://a.test/')))
        ->toThrow(UniqueVisitorIdentifierRequired::class);

    Plausible::assertNothingCaptured();
});

it('refuses a queued batch without a visitor, like the real client', function () {
    Bus::fake();
    Plausible::fake();

    expect(fn () => Plausible::events()->captureManyQueued([CaptureEventRequest::make(site: 'a.test', url: 'https://a.test/')]))
        ->toThrow(UniqueVisitorIdentifierRequired::class);

    Plausible::assertNothingCaptured();
    Bus::assertNothingBatched();
});

it('batches the real visitor under the fake', function () {
    Bus::fake();
    Plausible::fake();

    Plausible::events()->setVisitor('Agent/1.0', '198.51.100.7')->captureManyQueued([
        CaptureEventRequest::make(site: 'a.test', url: 'https://a.test/1'),
    ]);

    Bus::assertBatched(fn ($batch) => $batch->jobs->every(
        fn (CaptureEventJob $job) => $job->userAgent === 'Agent/1.0' && $job->ipAddress === '198.51.100.7',
    ));
});

// A capture is recorded only once it went out like the real client's: a capture that throws
// in production (no visitor, a failed request) must not satisfy assertEventCaptured().
it('records nothing for a capture without a visitor', function (Closure $capture) {
    Plausible::fake();

    rescue(fn () => $capture(Plausible::events()), report: false);

    Plausible::assertNothingCaptured();
})->with([
    'capture()' => [fn ($events) => $events->capture(CaptureEventRequest::make(site: 'a.test', url: 'https://a.test/'))],
    'pageview()' => [fn ($events) => $events->pageview('https://a.test/', site: 'a.test')],
    'event()' => [fn ($events) => $events->event('Signup', 'https://a.test/', site: 'a.test')],
    'captureMany()' => [fn ($events) => $events->captureMany([CaptureEventRequest::make(site: 'a.test', url: 'https://a.test/')])],
]);

it('records nothing for a capture whose request fails', function () {
    Http::fake(['*' => Http::response('boom', 500)]);
    Plausible::fake();

    expect(fn () => Plausible::events()->setVisitor('Agent/1.0', '198.51.100.7')
        ->capture(CaptureEventRequest::make(site: 'a.test', url: 'https://a.test/')))
        ->toThrow(RequestException::class);

    Plausible::assertNothingCaptured();
});

it('records a capture that went out once', function () {
    Plausible::fake();

    Plausible::events()->setVisitor('Agent/1.0', '198.51.100.7')
        ->capture(CaptureEventRequest::make(site: 'a.test', url: 'https://a.test/'));

    Plausible::assertEventCapturedCount(1);
});
