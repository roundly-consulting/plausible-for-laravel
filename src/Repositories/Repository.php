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
     * Build the base client. Throttled surfaces pass `throw: false` so the
     * limiter callback can return the raw 429 for hcrl to read `Retry-After`;
     * `Response::throw()` is then applied after the limiter (see the concern).
     */
    protected function request(bool $throw = true): PendingRequest
    {
        $url = config('plausible.url');
        $key = config('plausible.key');

        if (! is_string($url) || $url === '') {
            throw MissingConfiguration::url();
        }

        if (! is_string($key) || $key === '') {
            throw MissingConfiguration::key();
        }

        $request = Http::baseUrl($url)
            ->withToken($key)
            ->timeout($this->timeout());

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
