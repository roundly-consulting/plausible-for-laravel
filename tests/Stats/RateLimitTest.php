<?php

declare(strict_types=1);

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RoundlyConsulting\HttpClientRateLimits\Facades\RateLimits;
use RoundlyConsulting\PackageToolkit\Contracts\HasRetryAfter;
use RoundlyConsulting\Plausible\Exceptions\RateLimitExceeded;
use RoundlyConsulting\Plausible\Requests\Events\CaptureEventRequest;
use RoundlyConsulting\Plausible\Requests\Sites\CreateSiteRequest;
use RoundlyConsulting\Plausible\Requests\Stats\AggregateRequest;

function aggregate(string $site = 'localhost'): void
{
    plausible()->stats()->aggregate(
        AggregateRequest::make(site: $site, metrics: ['visitors'], period: '30d'),
    );
}

function capture(string $site = 'localhost'): void
{
    plausible()->events()
        ->setVisitor('PestAgent', '127.0.0.1')
        ->capture(CaptureEventRequest::make(site: $site, url: 'https://'.$site.'/', name: 'pageview'));
}

function createSite(string $domain = 'example.com'): void
{
    plausible()->sites()->create(CreateSiteRequest::make(domain: $domain));
}

beforeEach(function () {
    Http::preventStrayRequests();
});

it('paces every stats request through its own bucket and lets it through', function () {
    $fake = RateLimits::fake();
    useHttpSnapshot('stats/aggregate');

    aggregate();

    $fake->assertAllowed('plausible:stats:app:localhost:localhost');
});

it('defers a stats request once the per-site budget is exhausted', function () {
    config()->set('plausible.rate_limits.stats.limit', 1);
    $fake = RateLimits::fake();
    useHttpSnapshot('stats/aggregate');

    aggregate();
    aggregate();

    $fake->assertDeferred('plausible:stats:app:localhost:localhost');
});

it('fails fast with a native RateLimitExceeded when the wait exceeds max_wait', function () {
    config()->set('plausible.rate_limits.stats.limit', 1);
    config()->set('plausible.rate_limits.stats.max_wait', 0);
    RateLimits::fake();
    useHttpSnapshot('stats/aggregate');

    aggregate();

    expect(fn () => aggregate())
        ->toThrow(RateLimitExceeded::class, 'Rate limit for [plausible:stats:app:localhost:localhost] exceeded.');
});

it('exposes the budget key and wait window on the fail-fast exception', function () {
    config()->set('plausible.rate_limits.stats.limit', 1);
    config()->set('plausible.rate_limits.stats.max_wait', 0);
    RateLimits::fake();
    useHttpSnapshot('stats/aggregate');

    aggregate();

    try {
        aggregate();
    } catch (RateLimitExceeded $e) {
        expect($e)->toBeInstanceOf(HasRetryAfter::class)
            ->and($e->key)->toBe('plausible:stats:app:localhost:localhost')
            ->and($e->retryAfterSeconds())->toBeGreaterThan(0);

        return;
    }

    $this->fail('Expected a RateLimitExceeded exception.');
});

it('bypasses the limiter entirely when the stats bucket is disabled but still throws on failure', function () {
    config()->set('plausible.rate_limits.stats.enabled', false);
    config()->set('plausible.rate_limits.stats.limit', 1);
    $fake = RateLimits::fake();
    Http::fake(['*' => Http::response(['error' => 'boom'], 500)]);

    expect(fn () => aggregate())->toThrow(RequestException::class);

    $fake->assertNothingDeferred();
});

it('reads the 429 Retry-After so the next stats request self-throttles', function () {
    $fake = RateLimits::fake();
    Http::fake(['*' => Http::response(['error' => 'rate limited'], 429, ['Retry-After' => '2'])]);

    expect(fn () => aggregate())->toThrow(RequestException::class);

    try {
        aggregate();
    } catch (RequestException) {
        // still the faked 429 — we only care it was deferred first.
    }

    $fake->assertDeferred('plausible:stats:app:localhost:localhost');
});

it('keeps a separate stats budget per site', function () {
    config()->set('plausible.rate_limits.stats.limit', 1);
    $fake = RateLimits::fake();
    useHttpSnapshot('stats/aggregate');

    aggregate('one.test');
    aggregate('two.test');

    $fake->assertNothingDeferred();
});

it('collapses to a per-host stats key when per_site is off', function () {
    config()->set('plausible.rate_limits.stats.per_site', false);
    config()->set('plausible.rate_limits.stats.limit', 1);
    $fake = RateLimits::fake();
    useHttpSnapshot('stats/aggregate');

    aggregate('one.test');
    aggregate('two.test');

    $fake->assertDeferred('plausible:stats:app:localhost');
});

it('throttles events ingestion through its own bucket', function () {
    config()->set('plausible.rate_limits.events.limit', 1);
    $fake = RateLimits::fake();
    useHttpSnapshot('events/capture');

    capture();
    capture();

    $fake->assertAllowed('plausible:events:app:localhost:localhost')
        ->assertDeferred('plausible:events:app:localhost:localhost');
});

it('throttles sites provisioning through its own bucket', function () {
    config()->set('plausible.rate_limits.sites.limit', 1);
    $fake = RateLimits::fake();
    useHttpSnapshot('sites/create');

    createSite();
    createSite();

    $fake->assertAllowed('plausible:sites:app:localhost:example.com')
        ->assertDeferred('plausible:sites:app:localhost:example.com');
});

it('applies configured jitter to the stats bucket', function () {
    config()->set('plausible.rate_limits.stats.jitter', 50);
    $fake = RateLimits::fake();
    useHttpSnapshot('stats/aggregate');

    aggregate();

    $fake->assertAllowed('plausible:stats:app:localhost:localhost');
});

it('keeps the three surface budgets isolated from one another', function () {
    config()->set('plausible.rate_limits.stats.limit', 1);
    $fake = RateLimits::fake();
    Http::fake([
        '*/api/v1/stats/aggregate*' => snapshot('stats/aggregate'),
        '*/api/event' => snapshot('events/capture'),
    ]);

    aggregate();  // uses the single stats slot
    capture();    // events bucket untouched

    $fake->assertAllowed('plausible:events:app:localhost:localhost')
        ->assertNothingDeferred();
});
