<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use RoundlyConsulting\Plausible\Jobs\CaptureEventJob;
use RoundlyConsulting\Plausible\Tests\Fixtures\SimpleTrackedModel;
use RoundlyConsulting\Plausible\Tests\Fixtures\TrackedOrder;

beforeEach(function () {
    config()->set('plausible.site', 'tracked.test');

    Schema::create('tracked_orders', function (Blueprint $table) {
        $table->id();
        $table->integer('total')->default(0);
    });

    Schema::create('simple_tracked_models', function (Blueprint $table) {
        $table->id();
    });
});

afterEach(function () {
    Schema::dropIfExists('tracked_orders');
    Schema::dropIfExists('simple_tracked_models');
});

it('dispatches a plausible event with props on create', function () {
    Queue::fake();

    TrackedOrder::create(['total' => 99]);

    Queue::assertPushed(
        CaptureEventJob::class,
        fn (CaptureEventJob $job) => $job->request->name === 'Order Placed'
            && $job->request->site === 'tracked.test'
            && $job->request->props['total'] === 99,
    );
});

it('tracks updates and deletes with their event names', function () {
    Queue::fake();

    $order = TrackedOrder::create(['total' => 10]);
    $order->update(['total' => 20]);
    $order->delete();

    Queue::assertPushed(CaptureEventJob::class, fn (CaptureEventJob $job) => $job->request->name === 'Order Updated');
    Queue::assertPushed(CaptureEventJob::class, fn (CaptureEventJob $job) => $job->request->name === 'Order Cancelled');
});

it('captures synchronously when the queue is disabled', function () {
    config()->set('plausible.tracking.queue', false);
    useHttpSnapshot('events/capture');

    TrackedOrder::create(['total' => 5]);

    Http::assertSent(
        fn ($request) => str_contains($request->url(), '/api/event') && $request['name'] === 'Order Placed',
    );
});

it('does nothing when no site is configured', function () {
    config()->set('plausible.site', null);
    Queue::fake();

    TrackedOrder::create(['total' => 1]);

    Queue::assertNotPushed(CaptureEventJob::class);
});

it('uses default props and falls back to configured visitor without a request', function () {
    config()->set('plausible.tracking.queue', false);
    config()->set('plausible.tracking.user_agent', 'ServerBot');
    config()->set('plausible.tracking.ip_address', '10.0.0.1');
    useHttpSnapshot('events/capture');

    // What Laravel binds in the console and on queue workers (SetRequestForConsole): a
    // route-less request whose Symfony defaults are User-Agent "Symfony" and 127.0.0.1.
    app()->instance('request', Request::create((string) config('app.url'), 'GET', [], [], [], $_SERVER));

    SimpleTrackedModel::create();

    Http::assertSent(
        fn ($request) => $request->hasHeader('User-Agent', 'ServerBot')
            && $request->hasHeader('X-Forwarded-For', '10.0.0.1')
            && $request['name'] === 'Thing Made'
            && ! array_key_exists('props', $request->data()),
    );
});

it('uses the request visitor when available', function () {
    config()->set('plausible.tracking.queue', false);
    useHttpSnapshot('events/capture');

    Route::get('/make-order', function () {
        TrackedOrder::create(['total' => 7]);

        return 'ok';
    });

    $this->withHeaders(['User-Agent' => 'Browser/1.0'])->get('/make-order')->assertOk();

    Http::assertSent(fn ($request) => $request->hasHeader('User-Agent', 'Browser/1.0'));
});

// A rolled-back write never happened, so it must not reach Plausible either.
it('tracks nothing for a create that is rolled back', function (bool $queued) {
    config()->set('plausible.tracking.queue', $queued);
    Queue::fake();
    Http::fake();

    rescue(fn () => DB::transaction(function () {
        TrackedOrder::create(['total' => 5]);

        throw new RuntimeException('rolled back');
    }), report: false);

    expect(TrackedOrder::query()->count())->toBe(0);
    Http::assertNothingSent();
    Queue::assertNotPushed(CaptureEventJob::class);
})->with(['sync' => [false], 'queued' => [true]]);

it('tracks a create inside a transaction once it commits', function (bool $queued) {
    config()->set('plausible.tracking.queue', $queued);
    Queue::fake();
    useHttpSnapshot('events/capture');

    DB::transaction(function () {
        TrackedOrder::create(['total' => 5]);

        Http::assertNothingSent();
        Queue::assertNotPushed(CaptureEventJob::class);
    });

    if ($queued) {
        Queue::assertPushed(CaptureEventJob::class, 1);
    } else {
        Http::assertSentCount(1);
    }
})->with(['sync' => [false], 'queued' => [true]]);
