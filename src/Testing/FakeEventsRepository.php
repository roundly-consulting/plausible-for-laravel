<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Testing;

use Illuminate\Http\Client\Response;
use RoundlyConsulting\Plausible\Repositories\EventsRepository;
use RoundlyConsulting\Plausible\Requests\Events\CaptureEventRequest;

/**
 * Recording {@see EventsRepository} used by {@see PlausibleFake}. It records the
 * captured request for later assertions, then performs the (faked) HTTP call so
 * callers still receive a {@see Response}.
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

    public function captureQueued(CaptureEventRequest $request): void
    {
        $this->fake->recordEvent($request);
    }
}
