<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Http;
use RoundlyConsulting\Plausible\Repositories\EventsRepository;
use RoundlyConsulting\Plausible\Repositories\GoalsRepository;
use RoundlyConsulting\Plausible\Repositories\SharedLinksRepository;
use RoundlyConsulting\Plausible\Repositories\SitesRepository;
use RoundlyConsulting\Plausible\Repositories\StatsRepository;
use RoundlyConsulting\Plausible\Results\HealthResult;
use RoundlyConsulting\Plausible\Testing\PlausibleFake;

class Plausible
{
    public function stats(): StatsRepository
    {
        return resolve(StatsRepository::class);
    }

    public function events(): EventsRepository
    {
        return resolve(EventsRepository::class);
    }

    public function sites(): SitesRepository
    {
        return resolve(SitesRepository::class);
    }

    public function goals(): GoalsRepository
    {
        return resolve(GoalsRepository::class);
    }

    public function sharedLinks(): SharedLinksRepository
    {
        return resolve(SharedLinksRepository::class);
    }

    /**
     * Verify the configured Plausible URL/key are reachable.
     */
    public function ping(): bool
    {
        return $this->health()->reachable;
    }

    /**
     * Inspect configuration and connectivity without throwing.
     */
    public function health(): HealthResult
    {
        $url = config('plausible.url');
        $key = config('plausible.key');
        $hasUrl = is_string($url) && $url !== '';
        $hasKey = is_string($key) && $key !== '';

        if (! $hasUrl || ! $hasKey) {
            return new HealthResult(
                $hasUrl,
                $hasKey,
                false,
                null,
                'Plausible is not fully configured. Set PLAUSIBLE_URL and PLAUSIBLE_API_KEY.',
            );
        }

        try {
            $response = Http::baseUrl($url)
                ->withToken($key)
                ->timeout((int) config('plausible.timeout', 10))
                ->get('/api/v1/sites');
        } catch (ConnectionException $exception) {
            return new HealthResult(true, true, false, null, 'Could not reach Plausible: '.$exception->getMessage());
        }

        $status = $response->status();

        if ($status === 401 || $status === 403) {
            return new HealthResult(true, true, false, $status, "Plausible is reachable but the API key was rejected (HTTP {$status}).");
        }

        if ($response->successful()) {
            return new HealthResult(true, true, true, $status, 'Plausible is reachable and the API key is accepted.');
        }

        return new HealthResult(true, true, true, $status, "Plausible is reachable (HTTP {$status}).");
    }

    /**
     * Swap the bound manager for a recording {@see PlausibleFake} and return it,
     * so host-application tests can assert on Plausible activity.
     */
    public static function fake(): PlausibleFake
    {
        $fake = new PlausibleFake;

        app()->instance(self::class, $fake);
        Facade::clearResolvedInstance(self::class);

        return $fake;
    }
}
