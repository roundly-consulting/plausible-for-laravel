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
     * True when Plausible is reachable and accepts the configured API key — see
     * {@see health()} for which endpoint is probed.
     */
    public function ping(?string $site = null): bool
    {
        return $this->health($site)->ok();
    }

    /**
     * Inspect configuration and connectivity without throwing.
     *
     * With a site (the argument, else `plausible.site`) the key is verified against the
     * Stats API (`GET /api/v1/stats/realtime/visitors`), which a Stats API key can reach.
     * Without one the only site-less endpoint is the Sites API (`GET /api/v1/sites`), which
     * refuses Stats API keys — the message then says so rather than blaming the key.
     * Anything but a 2xx fails: 5xx and 404 mean the instance (or `PLAUSIBLE_URL`) is wrong.
     */
    public function health(?string $site = null): HealthResult
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
                false,
                null,
                'Plausible is not fully configured. Set PLAUSIBLE_URL and PLAUSIBLE_API_KEY '.
                '(the key is needed for the Stats and Sites APIs; event capture works without it).',
            );
        }

        $site ??= $this->defaultSite();

        try {
            $request = Http::baseUrl($url)
                ->withToken($key)
                ->timeout((int) config('plausible.timeout', 10));

            $response = $site !== null
                ? $request->get('/api/v1/stats/realtime/visitors', ['site_id' => $site])
                : $request->get('/api/v1/sites', ['limit' => 1]);
        } catch (ConnectionException $exception) {
            return new HealthResult(true, true, false, false, null, 'Could not reach Plausible: '.$exception->getMessage());
        }

        $status = $response->status();

        return match (true) {
            $response->successful() => new HealthResult(true, true, true, true, $status, $site !== null
                ? "Plausible is reachable and the API key can read stats for {$site}."
                : 'Plausible is reachable and the API key is accepted by the Sites API.'),
            $status >= 500 => new HealthResult(true, true, false, false, $status, "Plausible answered with a server error (HTTP {$status})."),
            $status === 404 => new HealthResult(true, true, false, false, $status, 'Plausible API endpoint not found (HTTP 404). Check that PLAUSIBLE_URL points at your Plausible instance.'),
            $status === 429 => new HealthResult(true, true, true, false, $status, 'Plausible is rate-limiting this API key (HTTP 429), so it could not be verified. Retry later.'),
            in_array($status, [401, 402, 403], true) => new HealthResult(true, true, true, false, $status, $site !== null
                ? "Plausible rejected the API key for {$site} (HTTP {$status}). Check the key, and that the key's team owns the site."
                : "The API key cannot use the Sites API (HTTP {$status}). Stats API keys are refused there, and the Sites API needs a Sites API key (an Enterprise feature). Set PLAUSIBLE_SITE, or run `plausible:health --site=<domain>`, to verify a Stats API key against the Stats API instead."),
            default => new HealthResult(true, true, true, false, $status, "Plausible refused the health probe (HTTP {$status})."),
        };
    }

    private function defaultSite(): ?string
    {
        $site = config('plausible.site');

        return is_string($site) && $site !== '' ? $site : null;
    }
}
