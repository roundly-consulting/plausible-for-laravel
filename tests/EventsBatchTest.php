<?php

declare(strict_types=1);

use Illuminate\Bus\PendingBatch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use RoundlyConsulting\Plausible\Exceptions\UniqueVisitorIdentifierRequired;
use RoundlyConsulting\Plausible\Requests\Events\CaptureEventRequest;

it('captures many events synchronously', function () {
    useHttpSnapshot('events/capture');

    $responses = plausible()->events()->setVisitor('Agent/1.0', '5.5.5.5')->captureMany([
        CaptureEventRequest::make(site: 'localhost', url: 'https://x.test/1'),
        CaptureEventRequest::make(site: 'localhost', url: 'https://x.test/2'),
    ]);

    expect($responses)->toHaveCount(2);

    Http::assertSentCount(2);
});

it('dispatches a queued batch of captures', function () {
    Bus::fake();

    plausible()->events()->setVisitor('Agent/1.0', '5.5.5.5')->captureManyQueued([
        CaptureEventRequest::make(site: 'localhost', url: 'https://x.test/1'),
        CaptureEventRequest::make(site: 'localhost', url: 'https://x.test/2'),
    ]);

    Bus::assertBatched(
        fn (PendingBatch $batch) => $batch->name === 'plausible:capture' && $batch->jobs->count() === 2,
    );
});

it('throws when batching without visitor identifiers', function () {
    plausible()->events()->captureManyQueued([
        CaptureEventRequest::make(site: 'localhost', url: 'https://x.test/1'),
    ]);
})->throws(UniqueVisitorIdentifierRequired::class);
