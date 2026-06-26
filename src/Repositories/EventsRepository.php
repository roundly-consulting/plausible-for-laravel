<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Repositories;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
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
        return $this->request()
            ->post('/api/event', $request->toQuery()->toArray());
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

    protected function request(): PendingRequest
    {
        if (is_null($this->visitorUserAgent) || is_null($this->visitorIpAddress)) {
            throw UniqueVisitorIdentifierRequired::error();
        }

        return parent::request()
            ->withHeaders([
                'User-Agent' => $this->visitorUserAgent,
                'X-Forwarded-For' => $this->visitorIpAddress,
            ]);
    }
}
