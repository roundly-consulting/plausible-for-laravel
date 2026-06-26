<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Repositories;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use RoundlyConsulting\Plausible\Exceptions\UniqueVisitorIdentifierRequired;
use RoundlyConsulting\Plausible\Requests\Events\CaptureEventRequest;

final class EventsRepository extends Repository
{
    private ?string $visitorUserAgent = null;

    private ?string $visitorIpAddress = null;

    public function setVisitor(string $userAgent, string $ipAddress): self
    {
        $this->visitorUserAgent = $userAgent;
        $this->visitorIpAddress = $ipAddress;

        return $this;
    }

    public function capture(CaptureEventRequest $request): Response
    {
        return $this->request()
            ->post('/api/event', $request->toQuery()->toArray());
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
