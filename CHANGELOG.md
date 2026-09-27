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
- `Plausible::fake()` with assertions for captured events, queries, sites, goals and shared links.
