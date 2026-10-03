<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use RoundlyConsulting\PackageToolkit\Support\Config;
use RoundlyConsulting\Plausible\Requests\Events\CaptureEventRequest;
use RoundlyConsulting\Plausible\Support\PlausibleConfig;
use Symfony\Component\HttpFoundation\Response;

/**
 * Fires a server-side pageview for matched web routes after the response is
 * sent. Safe to apply globally or per route: it only tracks successful HTML
 * GET responses, and silently skips when no site is configured.
 *
 * Register per route with the `plausible.track` alias, optionally overriding the
 * site: `->middleware('plausible.track:my-site.com')`.
 */
final class TrackPageview
{
    public function handle(Request $request, Closure $next, ?string $site = null): mixed
    {
        if ($site !== null) {
            $request->attributes->set('plausible_site', $site);
        }

        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        if (! $this->shouldTrack($request, $response)) {
            return;
        }

        $site = $this->resolveSite($request);

        if ($site === null) {
            return;
        }

        $events = plausible()->events()->fromRequest($request);

        $capture = CaptureEventRequest::make(
            site: $site,
            url: $request->fullUrl(),
            name: 'pageview',
            referrer: $request->headers->get('referer'),
        );

        if (Config::boolean('plausible.tracking.queue', true)) {
            $events->captureQueued($capture);

            return;
        }

        $events->capture($capture);
    }

    private function shouldTrack(Request $request, Response $response): bool
    {
        if (! $request->isMethod('GET')) {
            return false;
        }

        if ($request->ajax() || $request->expectsJson()) {
            return false;
        }

        if ($response->getStatusCode() >= 300) {
            return false;
        }

        return str_contains((string) $response->headers->get('Content-Type'), 'text/html');
    }

    private function resolveSite(Request $request): ?string
    {
        $override = $request->attributes->get('plausible_site');

        if (is_string($override) && $override !== '') {
            return $override;
        }

        return PlausibleConfig::present(config('plausible.site'));
    }
}
