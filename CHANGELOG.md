# Changelog

All notable changes to `plausible-for-laravel` are documented in this file. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the project uses
[Semantic Versioning](https://semver.org/).

## Unreleased

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
