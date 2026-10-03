# Changelog

All notable changes to `plausible-for-laravel` are documented in this file. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the project uses
[Semantic Versioning](https://semver.org/).

## Unreleased

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
