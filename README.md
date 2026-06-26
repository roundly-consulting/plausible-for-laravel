<!-- roundly-hero:start -->
<p align="center">
  <a href="https://roundly-consulting.com/open-source/docs/plausible-for-laravel?utm_source=github&utm_medium=readme&utm_campaign=open-source&utm_content=plausible-for-laravel">
    <img src="art/hero.png" alt="Plausible for Laravel — Roundly open source" width="100%">
  </a>
</p>
<!-- roundly-hero:end -->

# Plausible for Laravel

A clean, expressive client for the [Plausible Analytics](https://plausible.io) API. Query your
stats, capture custom events, and manage sites from your Laravel application using a fluent,
strongly-typed request API — no manual HTTP plumbing required.

## Requirements

- PHP `^8.4`
- Laravel `^12.0` or `^13.0`
- A Plausible account (Cloud or self-hosted) and an API key

## Installation

Install the package via Composer:

```bash
composer require roundly-consulting/plausible-for-laravel
```

Optionally publish the config file:

```bash
php artisan vendor:publish --tag="plausible-config"
```

## Configuration

The published config lives at `config/plausible.php`:

```php
<?php

declare(strict_types=1);

return [
    'url' => env('PLAUSIBLE_URL'),
    'key' => env('PLAUSIBLE_API_KEY'),
];
```

| Key   | Type     | Default                  | Description |
|-------|----------|--------------------------|-------------|
| `url` | `string` | `env('PLAUSIBLE_URL')`   | Base URL of your Plausible instance, e.g. `https://plausible.io` (or your self-hosted domain). |
| `key` | `string` | `env('PLAUSIBLE_API_KEY')` | Your Plausible API key, used as a Bearer token on Stats and Sites requests. |

Set both in your `.env`:

```dotenv
PLAUSIBLE_URL=https://plausible.io
PLAUSIBLE_API_KEY=your-api-key
```

## Usage

You can reach the client three ways — the `plausible()` helper, the `Plausible` facade, or by
resolving `RoundlyConsulting\Plausible\Plausible` from the container. All three return the same
instance and expose `stats()`, `events()`, and `sites()`.

```php
use RoundlyConsulting\Plausible\Facades\Plausible;

plausible()->stats();      // helper
Plausible::stats();        // facade
app(\RoundlyConsulting\Plausible\Plausible::class)->stats(); // container
```

### Stats API

```php
use RoundlyConsulting\Plausible\Facades\Plausible;
use RoundlyConsulting\Plausible\Requests\Stats\AggregateRequest;
use RoundlyConsulting\Plausible\Requests\Stats\BreakdownRequest;
use RoundlyConsulting\Plausible\Requests\Stats\TimeseriesRequest;

$stats = Plausible::stats();

// Current number of visitors on the site
$visitors = $stats->realtime('my-site.com'); // int

// Aggregate metrics for the last month, compared with the previous period
$stats->aggregate(AggregateRequest::make(
    site: 'my-site.com',
    metrics: ['visitors', 'pageviews', 'bounce_rate'],
    compare: true,
));

// Timeseries of visitors using Firefox over the last 7 days
$stats->timeseries(TimeseriesRequest::make(
    site: 'my-site.com',
    metrics: ['visitors'],
    filters: ['visit:browser==Firefox'],
    interval: 'date',
    period: '7d',
));

// Breakdown of traffic sources for the last month
$stats->breakdown(BreakdownRequest::make(
    site: 'my-site.com',
    property: 'visit:source',
));
```

Every `*Request` accepts an optional `start` / `end` (`Illuminate\Support\Carbon`) for custom
date ranges. When `start` is set with `period: 'custom'`, the client formats the `date`
parameter for you (single date, or `start,end` when both are supplied).

`aggregate`, `timeseries`, and `breakdown` return an `Illuminate\Http\Client\Response`, so you
can call `->json()`, `->collect()`, etc.

### Events API

> The `setVisitor()` call is **required** before `capture()`. Plausible needs the visitor's
> user-agent and IP address to count unique visitors; calling `capture()` without them throws
> `RoundlyConsulting\Plausible\Exceptions\UniqueVisitorIdentifierRequired`.

```php
use RoundlyConsulting\Plausible\Facades\Plausible;
use RoundlyConsulting\Plausible\Requests\Events\CaptureEventRequest;

Plausible::events()
    ->setVisitor(userAgent: request()->userAgent(), ipAddress: request()->ip())
    ->capture(CaptureEventRequest::make(
        site: 'my-site.com',
        url: 'https://my-site.com/blog/some-post',
        referrer: 'https://affiliate-site.com',
        props: ['author' => 'jane'],
    ));
```

### Sites API

> The Sites Provisioning API requires a key with the appropriate scope on your Plausible plan.

```php
use RoundlyConsulting\Plausible\Facades\Plausible;
use RoundlyConsulting\Plausible\Requests\Sites\CreateSiteRequest;

Plausible::sites()->create(CreateSiteRequest::make(
    domain: 'my.domain.tld',
    timezone: 'Europe/Bratislava',
));

Plausible::sites()->delete('my.domain.tld');
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
