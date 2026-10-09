<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RoundlyConsulting\Plausible\Repositories\EventsRepository;
use RoundlyConsulting\Plausible\Requests\Events\CaptureEventRequest;

/**
 * Captures an event off the request cycle. The visitor's user-agent and IP are
 * stored as plain strings so the job stays serialisable. Batchable, so
 * `captureManyQueued()` can send many in one bus batch.
 */
final class CaptureEventJob implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly CaptureEventRequest $request,
        public readonly string $userAgent,
        public readonly string $ipAddress,
    ) {}

    public function handle(): void
    {
        if ($this->batch()?->cancelled() === true) {
            return;
        }

        resolve(EventsRepository::class)
            ->setVisitor($this->userAgent, $this->ipAddress)
            ->capture($this->request);
    }
}
