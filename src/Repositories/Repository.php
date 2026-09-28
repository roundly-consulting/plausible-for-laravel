<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Repositories;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RoundlyConsulting\Plausible\Concerns\InteractsWithRateLimits;
use RoundlyConsulting\Plausible\Exceptions\MissingConfiguration;

abstract class Repository
{
    use InteractsWithRateLimits;

    /**
     * The authenticated client for the Stats and Sites APIs: the base client plus the
     * API key as a Bearer token. Throttled surfaces pass `throw: false` so the limiter
     * callback can return the raw 429 for hcrl to read `Retry-After`;
     * `Response::throw()` is then applied after the limiter (see the concern).
     */
    protected function request(bool $throw = true): PendingRequest
    {
        $client = $this->client($throw);
        $key = config('plausible.key');

        if (! is_string($key) || $key === '') {
            throw MissingConfiguration::key();
        }

        return $client->withToken($key);
    }

    /**
     * The unauthenticated base client: instance URL, timeout and retries. The Events API
     * builds on this directly — Plausible's `/api/event` takes no API key.
     */
    protected function client(bool $throw = true): PendingRequest
    {
        $url = config('plausible.url');

        if (! is_string($url) || $url === '') {
            throw MissingConfiguration::url();
        }

        $request = Http::baseUrl($url)->timeout($this->timeout());

        if ($throw) {
            $request->throw();
        }

        if ($this->retryTimes() > 0) {
            $request->retry($this->retryTimes(), $this->retrySleep());
        }

        return $request;
    }

    /**
     * The configured default site (domain), or null when none is set.
     */
    public function defaultSite(): ?string
    {
        $site = config('plausible.site');

        return is_string($site) && $site !== '' ? $site : null;
    }

    protected function timeout(): int
    {
        return (int) config('plausible.timeout', 10);
    }

    protected function retryTimes(): int
    {
        return (int) config('plausible.retry.times', 0);
    }

    protected function retrySleep(): int
    {
        return (int) config('plausible.retry.sleep_ms', 100);
    }
}
