<?php

declare(strict_types=1);

use Illuminate\Bus\Batch;
use Illuminate\Bus\PendingBatch;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use RoundlyConsulting\Plausible\Exceptions\UniqueVisitorIdentifierRequired;
use RoundlyConsulting\Plausible\Facades\Plausible;
use RoundlyConsulting\Plausible\Jobs\CaptureEventJob;
use RoundlyConsulting\Plausible\Requests\Events\CaptureEventRequest;

/**
 * A real batch store on the test connection, so `Bus::batch()` runs unfaked on the sync queue.
 */
function useRealBatchStore(): void
{
    config()->set('queue.batching.database', config('database.default'));

    Schema::create('job_batches', function (Blueprint $table) {
        $table->string('id')->primary();
        $table->string('name');
        $table->integer('total_jobs');
        $table->integer('pending_jobs');
        $table->integer('failed_jobs');
        $table->longText('failed_job_ids');
        $table->mediumText('options')->nullable();
        $table->integer('cancelled_at')->nullable();
        $table->integer('created_at');
        $table->integer('finished_at')->nullable();
    });
}

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

it('dispatches a real batch when the bus is not faked', function () {
    useRealBatchStore();
    useHttpSnapshot('events/capture');

    $batch = plausible()->events()->setVisitor('Agent/1.0', '5.5.5.5')->captureManyQueued([
        CaptureEventRequest::make(site: 'localhost', url: 'https://x.test/1'),
        CaptureEventRequest::make(site: 'localhost', url: 'https://x.test/2'),
    ]);

    expect($batch)->toBeInstanceOf(Batch::class)
        ->and($batch->name)->toBe('plausible:capture')
        ->and($batch->totalJobs)->toBe(2);

    Http::assertSentCount(2);
    Http::assertSent(fn ($request) => $request->hasHeader('User-Agent', 'Agent/1.0')
        && $request->hasHeader('X-Forwarded-For', '5.5.5.5'));
});

it('dispatches a real batch under the fake when the bus is not faked', function () {
    useRealBatchStore();
    Plausible::fake();

    $batch = Plausible::events()->setVisitor('Agent/1.0', '5.5.5.5')->captureManyQueued([
        CaptureEventRequest::make(site: 'a.test', url: 'https://a.test/1'),
        CaptureEventRequest::make(site: 'a.test', url: 'https://a.test/2'),
    ]);

    expect($batch->totalJobs)->toBe(2);

    Plausible::assertEventCapturedCount(2);
});

it('sends nothing for a capture whose batch was cancelled', function () {
    Http::fake();

    [$job] = (new CaptureEventJob(
        CaptureEventRequest::make(site: 'localhost', url: 'https://x.test/1'),
        'Agent/1.0',
        '5.5.5.5',
    ))->withFakeBatch(cancelledAt: Carbon::now()->toImmutable());

    $job->handle();

    Http::assertNothingSent();
});

it('throws when batching without visitor identifiers', function () {
    plausible()->events()->captureManyQueued([
        CaptureEventRequest::make(site: 'localhost', url: 'https://x.test/1'),
    ]);
})->throws(UniqueVisitorIdentifierRequired::class);
