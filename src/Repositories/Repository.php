<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Repositories;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RoundlyConsulting\Plausible\Exceptions\MissingConfiguration;

abstract class Repository
{
    protected function request(): PendingRequest
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
            ->timeout($this->timeout())
            ->throw();

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
