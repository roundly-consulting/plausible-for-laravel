<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Plausible instance
    |--------------------------------------------------------------------------
    |
    | Base URL of your Plausible instance (Cloud or self-hosted) and the API key
    | sent as a Bearer token on Stats and Sites requests. Event capture (the
    | Events API) is unauthenticated: it needs only the URL and never sends the key.
    |
    */

    'url' => env('PLAUSIBLE_URL'),

    'key' => env('PLAUSIBLE_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Default site
    |--------------------------------------------------------------------------
    |
    | Optional default site (domain) used by fluent calls and the events helpers
    | when no site is given explicitly. Leave null to always pass the site.
    |
    */

    'site' => env('PLAUSIBLE_SITE'),

    /*
    |--------------------------------------------------------------------------
    | HTTP behaviour
    |--------------------------------------------------------------------------
    |
    | Request timeout in seconds, and optional automatic retries for transient
    | failures. Retries are off by default (times = 0).
    |
    */

    'timeout' => (int) env('PLAUSIBLE_TIMEOUT', 10),

    'retry' => [
        'times' => (int) env('PLAUSIBLE_RETRY_TIMES', 0),
        'sleep_ms' => (int) env('PLAUSIBLE_RETRY_SLEEP', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | Client-side rate limiting
    |--------------------------------------------------------------------------
    |
    | Outbound requests are paced through http-client-rate-limits so you proactively
    | stay under Plausible's quotas instead of hammering the API and eating 429s.
    | Each surface — `stats` (query API), `events` (ingestion) and `sites`
    | (provisioning: sites, goals, shared links) — has its own bucket, keyed
    | `plausible:{surface}:{owner}:{host}[:{site}]`.
    |
    | Plausible's API limit (600 requests/hour by default) applies per API key,
    | across every site the key can read — so `stats` and `sites` default to ONE
    | account-wide bucket (`per_site` false). `events` is unauthenticated and keeps
    | a bucket per site.
    |
    | Per bucket:
    |   enabled  — false runs the plain client with no limiter.
    |   owner    — folds several app instances sharing one API key into one window.
    |   limit    — max requests per `per` window.
    |   per      — window size: second|minute|hour|day.
    |   adaptive — honour Plausible's 429 `Retry-After` (server-authoritative backoff).
    |   max_wait — ms; null = pace (wait), set = fail fast with RateLimitExceeded.
    |   jitter   — ms of random spread added to defers, null = none.
    |   per_site — include the site in the key (a separate budget per site). Only
    |              turn it on for stats/sites if your key really has per-site quotas.
    |
    | For a budget shared across workers/servers, point the limiter's store at
    | Cache/Redis/Database via HTTP_CLIENT_RATE_LIMITS_STORE (see that package).
    |
    */

    'rate_limits' => [
        'stats' => [
            'enabled' => (bool) env('PLAUSIBLE_STATS_RATELIMIT_ENABLED', true),
            'owner' => env('PLAUSIBLE_RATELIMIT_OWNER', 'app'),
            'limit' => (int) env('PLAUSIBLE_STATS_RATELIMIT', 600),
            'per' => env('PLAUSIBLE_STATS_RATELIMIT_PER', 'hour'),
            'adaptive' => (bool) env('PLAUSIBLE_STATS_RATELIMIT_ADAPTIVE', true),
            'max_wait' => env('PLAUSIBLE_STATS_RATELIMIT_MAX_WAIT'),
            'jitter' => env('PLAUSIBLE_STATS_RATELIMIT_JITTER'),
            'per_site' => (bool) env('PLAUSIBLE_STATS_RATELIMIT_PER_SITE', false),
        ],

        'events' => [
            'enabled' => (bool) env('PLAUSIBLE_EVENTS_RATELIMIT_ENABLED', true),
            'owner' => env('PLAUSIBLE_RATELIMIT_OWNER', 'app'),
            'limit' => (int) env('PLAUSIBLE_EVENTS_RATELIMIT', 6000),
            'per' => env('PLAUSIBLE_EVENTS_RATELIMIT_PER', 'minute'),
            'adaptive' => (bool) env('PLAUSIBLE_EVENTS_RATELIMIT_ADAPTIVE', true),
            'max_wait' => env('PLAUSIBLE_EVENTS_RATELIMIT_MAX_WAIT'),
            'jitter' => env('PLAUSIBLE_EVENTS_RATELIMIT_JITTER'),
            'per_site' => (bool) env('PLAUSIBLE_EVENTS_RATELIMIT_PER_SITE', true),
        ],

        'sites' => [
            'enabled' => (bool) env('PLAUSIBLE_SITES_RATELIMIT_ENABLED', true),
            'owner' => env('PLAUSIBLE_RATELIMIT_OWNER', 'app'),
            'limit' => (int) env('PLAUSIBLE_SITES_RATELIMIT', 60),
            'per' => env('PLAUSIBLE_SITES_RATELIMIT_PER', 'hour'),
            'adaptive' => (bool) env('PLAUSIBLE_SITES_RATELIMIT_ADAPTIVE', true),
            'max_wait' => env('PLAUSIBLE_SITES_RATELIMIT_MAX_WAIT'),
            'jitter' => env('PLAUSIBLE_SITES_RATELIMIT_JITTER'),
            'per_site' => (bool) env('PLAUSIBLE_SITES_RATELIMIT_PER_SITE', false),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Server-side tracking
    |--------------------------------------------------------------------------
    |
    | Defaults for automatic tracking via the `TrackPageview` middleware and the
    | `Trackable` model trait. `queue` dispatches captures onto the queue so they
    | never block the response. `user_agent` / `ip_address` are the visitor
    | identifiers used when no HTTP request is in scope (queue, console, model
    | events) — Plausible requires both to attribute a visit.
    |
    */

    'tracking' => [
        'queue' => (bool) env('PLAUSIBLE_TRACKING_QUEUE', true),

        'user_agent' => env('PLAUSIBLE_TRACKING_USER_AGENT', 'PlausibleForLaravel'),

        'ip_address' => env('PLAUSIBLE_TRACKING_IP', '127.0.0.1'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Tracking script
    |--------------------------------------------------------------------------
    |
    | Powers the `@plausibleScript` Blade directive and the `<x-plausible-script>`
    | component. `domain` is the `data-domain` attribute (falls back to `site`);
    | `src` is the hosted Plausible script URL — swap it for your self-hosted host
    | or a script variant (e.g. `script.tagged-events.js`).
    |
    */

    'script' => [
        'domain' => env('PLAUSIBLE_SCRIPT_DOMAIN'),

        'src' => env('PLAUSIBLE_SCRIPT_SRC', 'https://plausible.io/js/script.js'),
    ],
];
