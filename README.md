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
    'site' => env('PLAUSIBLE_SITE'),
    'timeout' => (int) env('PLAUSIBLE_TIMEOUT', 10),
    'retry' => [
        'times' => (int) env('PLAUSIBLE_RETRY_TIMES', 0),
        'sleep_ms' => (int) env('PLAUSIBLE_RETRY_SLEEP', 100),
    ],
];
```

| Key              | Type      | Default | Env                     | Description |
|------------------|-----------|---------|-------------------------|-------------|
| `url`            | `string`  | —       | `PLAUSIBLE_URL`         | Base URL of your Plausible instance, e.g. `https://plausible.io` (or your self-hosted domain). Required. |
| `key`            | `string`  | —       | `PLAUSIBLE_API_KEY`     | Your Plausible API key, used as a Bearer token on Stats and Sites requests. Required. |
| `site`           | `?string` | `null`  | `PLAUSIBLE_SITE`        | Optional default site (domain). When set, fluent calls and the events helpers can omit the site. |
| `timeout`        | `int`     | `10`    | `PLAUSIBLE_TIMEOUT`     | Per-request timeout in seconds. |
| `retry.times`    | `int`     | `0`     | `PLAUSIBLE_RETRY_TIMES` | Number of automatic retries on transient failures. `0` disables retries. |
| `retry.sleep_ms` | `int`     | `100`   | `PLAUSIBLE_RETRY_SLEEP` | Milliseconds to wait between retries. |

Missing `url` or `key` throws `RoundlyConsulting\Plausible\Exceptions\MissingConfiguration`
(a `PlausibleException`) on the first call, rather than sending a broken request.

Set the essentials in your `.env`:

```dotenv
PLAUSIBLE_URL=https://plausible.io
PLAUSIBLE_API_KEY=your-api-key
PLAUSIBLE_SITE=my-site.com
```

### Where it shines

Built for backend platforms that track events server-side and read analytics back into their
own UI — marketing sites, client-zone / SaaS dashboards, and custom CRMs that want traffic and
conversion numbers inside their own admin instead of a separate tool.

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

### Stats API — fluent

The fluent builder is the recommended way to read stats. It returns typed result objects, so
you stop hand-parsing JSON, and accepts enums for type-safe metrics, periods, intervals and
properties (plain strings still work everywhere).

```php
use RoundlyConsulting\Plausible\Facades\Plausible;
use RoundlyConsulting\Plausible\Enums\Metric;
use RoundlyConsulting\Plausible\Enums\Period;
use RoundlyConsulting\Plausible\Enums\Property;

// Live visitors on the site right now
$live = Plausible::stats()->for('my-site.com')->realtime(); // int

// Aggregate metrics for a custom range, compared with the previous period
$result = Plausible::stats()
    ->for('my-site.com')                          // omit when PLAUSIBLE_SITE is set
    ->metrics(Metric::Visitors, Metric::Pageviews, Metric::BounceRate)
    ->between(now()->subDays(7), now())           // auto-sets period=custom
    ->compare()
    ->aggregate();                                // -> AggregateResult

$visitors = $result->metric(Metric::Visitors);    // typed int
$change   = $result->change(Metric::Visitors);    // comparison delta (or null)
$raw      = $result->response();                  // escape hatch to the Response

// Day-by-day trend you can chart
$points = Plausible::stats()
    ->for('my-site.com')
    ->metrics(Metric::Visitors)
    ->period(Period::ThirtyDays)
    ->timeseries()                                // -> TimeseriesResult
    ->points();                                   // list<TimeseriesPoint>

// Top traffic sources, ranked and paginated
$rows = Plausible::stats()
    ->for('my-site.com')
    ->metrics(Metric::Visitors)
    ->perPage(20)
    ->breakdown(Property::VisitSource)            // -> BreakdownResult
    ->rows();                                      // list<BreakdownRow>
```

`since(Carbon $start)` and `between(Carbon $start, Carbon $end)` both set `period=custom` for
you, so a date range can never be silently ignored.

### Stats API — explicit requests

The request objects remain available for power users; the repository methods return the raw
`Illuminate\Http\Client\Response`, and a `*Result` variant returns the typed object.

```php
use RoundlyConsulting\Plausible\Requests\Stats\AggregateRequest;

$response = Plausible::stats()->aggregate(AggregateRequest::make(
    site: 'my-site.com',
    metrics: ['visitors', 'pageviews', 'bounce_rate'],
    compare: true,
));

$result = Plausible::stats()->aggregateResult(AggregateRequest::make(
    site: 'my-site.com',
    metrics: [Metric::Visitors],
)); // -> AggregateResult
```

`timeseriesResult()` and `breakdownResult()` exist too.

### Stats v2 query endpoint

The consolidated `/api/v2/query` endpoint unlocks dimensions, ordering, pagination and richer
metrics. Drive it from the same builder with `dimensions()`, `orderBy()`, `include()`, array
`filter()`s and a `run()` terminal:

```php
use RoundlyConsulting\Plausible\Enums\Dimension;
use RoundlyConsulting\Plausible\Enums\Metric;

$result = Plausible::stats()
    ->for('my-site.com')
    ->metrics(Metric::Visitors, Metric::BounceRate)
    ->dimensions(Dimension::EventPage)
    ->filter(['is', 'visit:browser', ['Chrome']])
    ->orderBy(['visitors', 'desc'])
    ->between(now()->subDays(7), now())
    ->perPage(10)
    ->run();                                       // -> QueryResult

foreach ($result->rows() as $row) {
    $row->dimension(0);   // e.g. "/pricing"
    $row->metric(0);      // visitors
}
```

Or build the request explicitly with `QueryRequest` and call `Plausible::stats()->runQuery($request)`.

> Stats v2 is available on Plausible Cloud and recent self-hosted versions. Older self-hosted
> instances may only expose the v1 endpoints above.

### Events API

Track conversions server-side — even where front-end JavaScript can't reach (API calls,
webhooks, queued jobs).

> Plausible needs the visitor's user-agent and IP to count unique visitors. Provide them with
> `setVisitor()` or `fromRequest()`; capturing without them throws
> `RoundlyConsulting\Plausible\Exceptions\UniqueVisitorIdentifierRequired`.

```php
use RoundlyConsulting\Plausible\Facades\Plausible;
use RoundlyConsulting\Plausible\Requests\Events\CaptureEventRequest;
use RoundlyConsulting\Plausible\DataTransferObjects\Revenue;

// Lift the visitor from the current request, send a pageview in one call
Plausible::events()->fromRequest(request())->pageview('https://my-site.com/blog/x');

// A custom revenue event, fired on the queue so it never blocks the response
Plausible::events()
    ->fromRequest(request())
    ->captureQueued(CaptureEventRequest::make(
        site: 'my-site.com',
        url: 'https://my-site.com/checkout',
        name: 'purchase',
        revenue: new Revenue(currency: 'EUR', amount: 49.0),
        props: ['plan' => 'pro'],
    ));

// The explicit, synchronous form still works
Plausible::events()
    ->setVisitor(userAgent: request()->userAgent(), ipAddress: request()->ip())
    ->capture(CaptureEventRequest::make(
        site: 'my-site.com',
        url: 'https://my-site.com/blog/some-post',
        referrer: 'https://affiliate-site.com',
        props: ['author' => 'jane'],
    ));
```

`fromRequest()` and `pageview()` use the configured default site when none is given.
`captureQueued()` dispatches `RoundlyConsulting\Plausible\Jobs\CaptureEventJob` onto your queue.

### Sites API

> The Sites API requires a key with the appropriate scope on your Plausible plan. Listing and
> some management endpoints may require an enterprise plan on self-hosted instances.

```php
use RoundlyConsulting\Plausible\Facades\Plausible;
use RoundlyConsulting\Plausible\Requests\Sites\CreateSiteRequest;
use RoundlyConsulting\Plausible\Requests\Sites\UpdateSiteRequest;

Plausible::sites()->create(CreateSiteRequest::make(
    domain: 'my.domain.tld',
    timezone: 'Europe/Bratislava',
));

$site  = Plausible::sites()->get('my.domain.tld');   // -> SiteResult
$sites = Plausible::sites()->list();                 // -> list<SiteResult>

Plausible::sites()->update(UpdateSiteRequest::make(
    site: 'my.domain.tld',
    domain: 'new.domain.tld',
));

Plausible::sites()->delete('my.domain.tld');
```

`get`, `list` and `update` return typed `SiteResult` objects; `create` and `delete` return the
raw `Response`.

### Enums

Type-safe, discoverable parameters live under `RoundlyConsulting\Plausible\Enums`:
`Metric`, `Period`, `Interval`, `Property` (v1 breakdown), `Dimension` (v2) and
`FilterOperator` (v2). For a custom event property use `Property::custom('plan')` or
`Dimension::custom('plan')`, which return the `event:props:plan` string. Anywhere an enum is
accepted, the equivalent string still works.

## Testing

In your application's tests, swap the client into a recording fake with `Plausible::fake()` —
no real HTTP — then assert on what was captured or queried:

```php
use RoundlyConsulting\Plausible\Facades\Plausible;
use RoundlyConsulting\Plausible\Requests\Events\CaptureEventRequest;

Plausible::fake();

$this->post('/checkout', [/* ... */]);

Plausible::assertEventCaptured(
    fn (CaptureEventRequest $event) => $event->name === 'purchase'
        && $event->revenue?->amount === 49.0,
);

Plausible::assertQueried(fn ($request, string $type) => $type === 'aggregate');
Plausible::assertSiteCreated('new-tenant.com');
```

Available assertions: `assertEventCaptured`, `assertEventNotCaptured`, `assertNothingCaptured`,
`assertEventCapturedCount`, `assertQueried`, `assertNothingQueried`, `assertSiteCreated`,
`assertSiteDeleted`, `assertSiteUpdated`.

Run the package's own test suite with:

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
