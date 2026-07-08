<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Repositories;

use Illuminate\Bus\Batch;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use RoundlyConsulting\Plausible\Exceptions\MissingConfiguration;
use RoundlyConsulting\Plausible\Exceptions\UniqueVisitorIdentifierRequired;
use RoundlyConsulting\Plausible\Jobs\CaptureEventJob;
use RoundlyConsulting\Plausible\Requests\Events\CaptureEventRequest;

class EventsRepository extends Repository
{
    protected ?string $visitorUserAgent = null;

    protected ?string $visitorIpAddress = null;

    public function setVisitor(string $userAgent, string $ipAddress): static
    {
        $this->visitorUserAgent = $userAgent;
        $this->visitorIpAddress = $ipAddress;

        return $this;
    }

    /**
     * Lift the visitor's user-agent and IP from the current HTTP request.
     */
    public function fromRequest(Request $request): static
    {
        return $this->setVisitor(
            $request->userAgent() ?? '',
            $request->ip() ?? '',
        );
    }

    public function capture(CaptureEventRequest $request): Response
    {
        return $this->throttled(
            'events',
            $request->site,
            fn (): Response => $this->request(throw: false)
                ->post('/api/event', $request->toQuery()->toArray()),
        );
    }

    /**
     * Record a pageview for the given URL, using the configured default site
     * when none is provided.
     */
    public function pageview(string $url, ?string $site = null): Response
    {
        $site ??= $this->defaultSite();

        if ($site === null) {
            throw MissingConfiguration::site();
        }

        return $this->capture(CaptureEventRequest::make(site: $site, url: $url, name: 'pageview'));
    }

    /**
     * Dispatch the capture onto the queue so it never blocks the response.
     */
    public function captureQueued(CaptureEventRequest $request): void
    {
        if (is_null($this->visitorUserAgent) || is_null($this->visitorIpAddress)) {
            throw UniqueVisitorIdentifierRequired::error();
        }

        CaptureEventJob::dispatch($request, $this->visitorUserAgent, $this->visitorIpAddress);
    }

    /**
     * Capture many events synchronously, returning each response in order.
     *
     * @param  iterable<CaptureEventRequest>  $events
     * @return list<Response>
     */
    public function captureMany(iterable $events): array
    {
        $responses = [];

        foreach ($events as $event) {
            $responses[] = $this->capture($event);
        }

        return $responses;
    }

    /**
     * Capture many events on the queue as a single batch — ideal for bulk or
     * historical imports.
     *
     * @param  iterable<CaptureEventRequest>  $events
     */
    public function captureManyQueued(iterable $events): Batch
    {
        if (is_null($this->visitorUserAgent) || is_null($this->visitorIpAddress)) {
            throw UniqueVisitorIdentifierRequired::error();
        }

        $jobs = [];

        foreach ($events as $event) {
            $jobs[] = new CaptureEventJob($event, $this->visitorUserAgent, $this->visitorIpAddress);
        }

        return Bus::batch($jobs)->name('plausible:capture')->dispatch();
    }

    protected function request(bool $throw = true): PendingRequest
    {
        if (is_null($this->visitorUserAgent) || is_null($this->visitorIpAddress)) {
            throw UniqueVisitorIdentifierRequired::error();
        }

        return parent::request($throw)
            ->withHeaders([
                'User-Agent' => $this->visitorUserAgent,
                'X-Forwarded-For' => $this->visitorIpAddress,
            ]);
    }
}
