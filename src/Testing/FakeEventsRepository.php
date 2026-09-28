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
 * Recording {@see EventsRepository} used by {@see PlausibleFake}. It records the
 * captured request for later assertions, then performs the (faked) HTTP call so
 * callers still receive a {@see Response}.
 *
 * @internal
 */
final class FakeEventsRepository extends EventsRepository
{
    public function __construct(
        private readonly PlausibleFake $fake,
    ) {}

    public function capture(CaptureEventRequest $request): Response
    {
        $this->fake->recordEvent($request);

        return parent::capture($request);
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
