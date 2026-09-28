<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible;

use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\HtmlString;
use RoundlyConsulting\Plausible\Repositories\EventsRepository;
use RoundlyConsulting\Plausible\Repositories\GoalsRepository;
use RoundlyConsulting\Plausible\Repositories\SharedLinksRepository;
use RoundlyConsulting\Plausible\Repositories\SitesRepository;
use RoundlyConsulting\Plausible\Repositories\StatsRepository;
use RoundlyConsulting\Plausible\Results\HealthResult;
use RoundlyConsulting\Plausible\Support\ScriptTag;

/**
 * The package's public API: the root behind the Plausible facade, injectable by its own
 * class-string and bound as a singleton. Plausible is a remote API, so each area is a
 * resource client (a "repository") resolved through the container — `stats()`, `events()`,
 * `sites()`, `goals()`, `sharedLinks()` — rather than one action per endpoint.
 *
 * Not final on purpose: PlausibleFake extends it, so code that constructor-injects this
 * class still type-checks under `Plausible::fake()`.
 */
class PlausibleManager
{
    public function __construct(
        protected readonly Container $container,
    ) {}

    public function stats(): StatsRepository
    {
        return $this->container->make(StatsRepository::class);
    }

    public function events(): EventsRepository
    {
        return $this->container->make(EventsRepository::class);
    }

    public function sites(): SitesRepository
    {
        return $this->container->make(SitesRepository::class);
    }

    public function goals(): GoalsRepository
    {
        return $this->container->make(GoalsRepository::class);
    }

    public function sharedLinks(): SharedLinksRepository
    {
        return $this->container->make(SharedLinksRepository::class);
    }

    /**
     * The hosted Plausible tracking-script tag, for Blade, Inertia or any PHP view. The
     * domain defaults to `plausible.script.domain`, then `plausible.site`; with neither set
     * this is an empty string. `@plausibleScript` and `<x-plausible-script>` render this.
     */
    public function script(?string $domain = null): HtmlString
    {
        return ScriptTag::render($domain);
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
}
