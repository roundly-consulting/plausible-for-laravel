<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Plausible instance
    |--------------------------------------------------------------------------
    |
    | Base URL of your Plausible instance (Cloud or self-hosted) and the API key
    | used as a Bearer token on Stats and Sites requests.
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
