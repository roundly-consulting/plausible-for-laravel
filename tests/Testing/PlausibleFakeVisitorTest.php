<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Bus;
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
