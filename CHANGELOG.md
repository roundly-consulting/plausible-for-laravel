# Changelog

All notable changes to `plausible-for-laravel` are documented in this file. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the project uses
[Semantic Versioning](https://semver.org/).

## Unreleased

### Fixed

- `events()->captureManyQueued()` no longer throws "does not use the Batchable trait" when the bus
  is not faked: `CaptureEventJob` is batchable, and a job in a cancelled batch sends nothing.
- `thisYear()` (or a `year` period) on `aggregate()`, `timeseries()` and `breakdown()` no longer
  gets a 400: the v1 API has no `year`, so it is sent as `period=custom` from 1 January through
  today. `allTime()` (an `all` period) on those terminals now throws `UnsupportedRequest`
  pointing to `run()` instead of sending a request Plausible rejects. `run()` is unchanged.
- `run()` sends `pagination.limit` whenever it sends an offset or `perPage()` was called, so
  `page(2)->run()` returns rows 101–200 and `perPage(100)->run()` at most 100 rows, instead of
  falling back to Plausible's v2 default of 10000.
- **Behaviour change:** `UpdateSiteRequest` throws `UnsupportedRequest` when `timezone` is set,
  and when no new `domain` is given. Plausible's `PUT /api/v1/sites/:site_id` changes only the
  domain: a timezone was silently dropped (or, on its own, answered with a 400). The `timezone`
  parameter stays in the signature.
- The `Trackable` trait uses `plausible.tracking.user_agent` / `ip_address` in the console and on
  queue workers, as documented. It used to send the console request's Symfony defaults
  (`User-Agent: Symfony`, `127.0.0.1`) instead.
- **Behaviour change:** `plausible.retry.*` retries only failed connections and `5xx` responses.
  A `4xx` (including a `429`) is no longer retried, and a timed-out `POST /api/event` is never
  retried, since Plausible may already have recorded the event (a retry could count it, and its
  revenue, twice). A `429`'s `Retry-After` now reaches the rate limiter with retries on, so the
  next call waits.

## 1.0.0 - 2026-10-03

Initial public release.

### Added

- A Plausible Analytics API client for Laravel through the `Plausible` facade and `plausible()`
  helper, for Plausible Cloud or self-hosted.
- A fluent, typed Stats API — `aggregate()`, `timeseries()`, `breakdown()`, `realtime()`, named
  period shortcuts, type-safe `where()` filters, caching, collections and auto-paginating
  `cursor()` / `each()` — plus the Stats v2 query endpoint via `run()`.
- Server-side event capture with the Events API: pageviews and custom events, sync, queued
  (`captureQueued()`) or batched (`captureMany()` / `captureManyQueued()`).
- Exact revenue on events with `Revenue::of()` / `Revenue::make()`, built on money-for-laravel.
- Automatic server-side pageview tracking with the `plausible.track` middleware, and model
  lifecycle event tracking with the `Trackable` trait.
- Sites, Goals and Shared links APIs returning typed result objects.
- The `@plausibleScript` Blade directive and `<x-plausible-script>` component for the hosted
  tracking script.
- `Plausible::ping()`, `Plausible::health()` and the `plausible:health` command.
- Typed enums (`Metric`, `Period`, `Interval`, `Property`, `Dimension`, `FilterOperator`,
  `GoalType`) with the enums-for-laravel helper surface.
- Client-side rate limiting per surface (stats, events, sites) with adaptive backoff on
  Plausible's `Retry-After`.
- `Plausible::events()->event($name, url:, props:, revenue:, site:, referrer:, interactive:)` — a
  custom-event shortcut that defaults the site like `pageview()`.
- `Plausible::script(?$domain)` returns the tracking-script tag as an `HtmlString` for Inertia or
  any PHP view; the `@plausibleScript` directive and `<x-plausible-script>` component render it.
- `Plausible::fake()` — a real static on the facade returning `Testing\PlausibleFake`, a subtype
  of `PlausibleManager` installed behind the facade and in the container (so `plausible()`, the
  `Trackable` trait and the middleware are recorded too). Asserts for captured events, queries,
  sites, goals and shared links, each with a negative twin (`assertSiteNotCreated`,
  `assertSiteNotDeleted`, `assertSiteNotUpdated`, `assertGoalNotCreated`, `assertGoalNotDeleted`,
  `assertSharedLinkNotCreated`).

### Changed

- The facade root is `RoundlyConsulting\Plausible\PlausibleManager` (was
  `RoundlyConsulting\Plausible\Plausible`, which shared the facade's short name); it takes the
  container in its constructor and `fake()` moved from it to the facade.
- `sites()->create()` returns a `SiteResult`; `sites()->delete()` and `goals()->delete()` return
  `bool` instead of the raw HTTP response.
- `Support\ScriptTag` is internal — use `Plausible::script()`.
- Outbound rate limits are built through the `RateLimits` facade of
  http-client-rate-limits-for-laravel.
- The `stats` and `sites` rate-limit buckets default to one budget per API key (`per_site` off),
  matching Plausible's per-key limit; `events` stays per site.
- `Plausible::ping()` / `health()` take an optional site; `HealthResult` gains `authorized`.

### Removed

- The shared-link `password` argument — Plausible's shared-links endpoint takes only a site and a
  name, so the link was created unprotected.

### Fixed

- `since()` sends a closed range: v1 `period=custom&date=start,today`, v2
  `date_range: [start, today]`. A v1 custom period with only a start is closed at today.
- Event capture neither requires nor sends the API key — the Events API is unauthenticated.
- The health check fails on `5xx` and `404`, probes the Stats API for the default site (or
  `plausible:health --site=`), and explains the key scope when the Sites API refuses a Stats key.
- `sites()->list()` and `goals()->list()` follow the `meta.after` cursor through every page.
- Site and goal ids are URL-encoded into request paths.
- `Plausible::fake()` refuses a queued capture without a visitor, like the real client.
- `Plausible::script()`, `@plausibleScript` and `<x-plausible-script>` render the publishable
  `plausible::components.script` view.
- Boolean env config (`*_ENABLED`, `*_ADAPTIVE`, `*_PER_SITE`, `PLAUSIBLE_TRACKING_QUEUE`) accepts
  `on`/`off`, `yes`/`no`, `1`/`0` as well as `true`/`false`.
