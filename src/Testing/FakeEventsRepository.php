<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Testing;

use Illuminate\Bus\Batch;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Bus;
use RoundlyConsulting\Plausible\Jobs\CaptureEventJob;
use RoundlyConsulting\Plausible\Repositories\EventsRepository;
use RoundlyConsulting\Plausible\Requests\Events\CaptureEventRequest;

/**
 * Recording {@see EventsRepository} used by {@see PlausibleFake}. It performs the
 * (faked) HTTP call so callers still receive a {@see Response}, then records the
 * captured request for later assertions.
 *
 * @internal
 */
final class FakeEventsRepository extends EventsRepository
{
    public function __construct(
        private readonly PlausibleFake $fake,
    ) {}

    /**
     * Records only once the capture went out like the real client's — a capture without a
     * visitor, or whose request fails, throws in production and records nothing here.
     */
    public function capture(CaptureEventRequest $request): Response
    {
        $response = parent::capture($request);

        $this->fake->recordEvent($request);

        return $response;
    }

    /**
     * Records instead of dispatching — but refuses a missing visitor exactly like the real
     * client, so a test cannot pass for code that throws in production.
     */
    public function captureQueued(CaptureEventRequest $request): void
    {
        $this->requireVisitor();

        $this->fake->recordEvent($request);
    }

    /**
     * Records each event, then dispatches the batch for real (the method returns a
     * {@see Batch}) — pair it with `Bus::fake()` in tests that should not touch the queue.
     */
    public function captureManyQueued(iterable $events): Batch
    {
        $this->requireVisitor();

        $jobs = [];

        foreach ($events as $event) {
            $this->fake->recordEvent($event);
            $jobs[] = new CaptureEventJob($event, $this->visitorUserAgent, $this->visitorIpAddress);
        }

        return Bus::batch($jobs)->name('plausible:capture')->dispatch();
    }
}
