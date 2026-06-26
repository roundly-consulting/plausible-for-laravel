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
];
