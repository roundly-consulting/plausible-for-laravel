<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use RoundlyConsulting\Plausible\Jobs\CaptureEventJob;

beforeEach(function () {
    config()->set('plausible.site', 'tracked.test');
});

it('queues a pageview for a successful html GET', function () {
    Queue::fake();

    Route::middleware('plausible.track')->get('/tracked', fn () => 'ok');

    $this->get('/tracked')->assertOk();

    Queue::assertPushed(
        CaptureEventJob::class,
        fn (CaptureEventJob $job) => $job->request->name === 'pageview'
            && $job->request->site === 'tracked.test'
            && str_contains($job->request->url, '/tracked'),
    );
});

it('does not track json requests', function () {
    Queue::fake();

    Route::middleware('plausible.track')->get('/api/data', fn () => ['ok' => true]);

    $this->getJson('/api/data')->assertOk();

    Queue::assertNotPushed(CaptureEventJob::class);
});

it('does not track non-successful responses', function () {
    Queue::fake();

    Route::middleware('plausible.track')->get('/missing', fn () => abort(404));

    $this->get('/missing')->assertNotFound();

    Queue::assertNotPushed(CaptureEventJob::class);
});

it('does not track non-GET requests', function () {
    Queue::fake();

    Route::middleware('plausible.track')->post('/submit', fn () => 'ok');

    $this->post('/submit')->assertOk();

    Queue::assertNotPushed(CaptureEventJob::class);
});

it('captures synchronously when the queue is disabled', function () {
    config()->set('plausible.tracking.queue', false);
    useHttpSnapshot('events/capture');

    Route::middleware('plausible.track')->get('/tracked', fn () => 'ok');

    $this->get('/tracked')->assertOk();

    Http::assertSent(fn ($request) => str_contains($request->url(), '/api/event'));
});

it('honours a site override passed to the middleware', function () {
    config()->set('plausible.site', null);
    Queue::fake();

    Route::middleware('plausible.track:override.test')->get('/tracked', fn () => 'ok');

    $this->get('/tracked')->assertOk();

    Queue::assertPushed(
        CaptureEventJob::class,
        fn (CaptureEventJob $job) => $job->request->site === 'override.test',
    );
});

it('skips tracking when no site is configured', function () {
    config()->set('plausible.site', null);
    Queue::fake();

    Route::middleware('plausible.track')->get('/tracked', fn () => 'ok');

    $this->get('/tracked')->assertOk();

    Queue::assertNotPushed(CaptureEventJob::class);
});

it('records the referer as the pageview referrer', function () {
    Queue::fake();

    Route::middleware('plausible.track')->get('/tracked', fn () => 'ok');

    $this->withHeaders(['referer' => 'https://example.com'])->get('/tracked')->assertOk();

    Queue::assertPushed(
        CaptureEventJob::class,
        fn (CaptureEventJob $job) => $job->request->referrer === 'https://example.com',
    );
});
