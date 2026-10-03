<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use RoundlyConsulting\HttpClientRateLimits\Facades\RateLimits;
use RoundlyConsulting\PackageToolkit\Exceptions\InvalidConfigurationException;
use RoundlyConsulting\Plausible\Requests\Stats\AggregateRequest;

/*
 * Env values reach config as strings: `off`, `no` and `0` must switch a flag off, and
 * `on`/`yes`/`1` on — a `(bool)` cast would read every non-empty string as true.
 */

function aggregateFor(string $site): void
{
    plausible()->stats()->aggregate(AggregateRequest::make(site: $site));
}

it('disables a rate-limit bucket from an env string', function (string $off) {
    config()->set('plausible.rate_limits.stats.enabled', $off);
    config()->set('plausible.rate_limits.stats.limit', 1);
    $fake = RateLimits::fake();
    useHttpSnapshot('stats/aggregate');

    aggregateFor('localhost');
    aggregateFor('localhost');

    $fake->assertNothingDeferred();
})->with(['off', 'no', '0', 'false']);

it('turns per_site on from an env string', function (string $on) {
    config()->set('plausible.rate_limits.stats.per_site', $on);
    config()->set('plausible.rate_limits.stats.limit', 1);
    $fake = RateLimits::fake();
    useHttpSnapshot('stats/aggregate');

    aggregateFor('one.test');
    aggregateFor('two.test');

    $fake->assertNothingDeferred();
})->with(['on', 'yes', '1', 'true']);

it('keeps per_site off from an env string', function () {
    config()->set('plausible.rate_limits.stats.per_site', 'off');
    config()->set('plausible.rate_limits.stats.limit', 1);
    $fake = RateLimits::fake();
    useHttpSnapshot('stats/aggregate');

    aggregateFor('one.test');
    aggregateFor('two.test');

    $fake->assertDeferred('plausible:stats:app:localhost');
});

it('turns adaptive backoff off from an env string', function () {
    config()->set('plausible.rate_limits.stats.adaptive', 'off');
    $fake = RateLimits::fake();
    Http::fake(['*' => Http::response(['error' => 'rate limited'], 429, ['Retry-After' => '2'])]);

    try {
        aggregateFor('localhost');
    } catch (Throwable) {
    }

    try {
        aggregateFor('localhost');
    } catch (Throwable) {
    }

    $fake->assertNothingDeferred();
});

it('turns adaptive backoff on from an env string', function () {
    config()->set('plausible.rate_limits.stats.adaptive', 'yes');
    $fake = RateLimits::fake();
    Http::fake(['*' => Http::response(['error' => 'rate limited'], 429, ['Retry-After' => '2'])]);

    try {
        aggregateFor('localhost');
    } catch (Throwable) {
    }

    try {
        aggregateFor('localhost');
    } catch (Throwable) {
    }

    $fake->assertDeferred('plausible:stats:app:localhost');
});

it('captures synchronously when tracking.queue is an off string', function () {
    config()->set('plausible.site', 'localhost');
    config()->set('plausible.tracking.queue', 'off');
    Queue::fake();
    useHttpSnapshot('events/capture');

    Route::middleware('plausible.track')->get('/tracked', fn () => 'ok');

    $this->get('/tracked')->assertOk();

    Queue::assertNothingPushed();
    Http::assertSent(fn ($request) => str_contains($request->url(), '/api/event'));
});

it('reports string-disabled flags as off in about', function () {
    config()->set('plausible.tracking.queue', 'off');
    config()->set('plausible.rate_limits.stats.enabled', 'no');

    Artisan::call('about', ['--only' => 'plausible', '--json' => true]);
    $about = json_decode(Artisan::output(), true);

    expect($about['plausible']['queued_tracking'])->toBe('OFF')
        ->and($about['plausible']['rate_limiting'])->toBe('events, sites');
});

it('refuses a mistyped rate-limit flag, naming the full key (strict config)', function (string $leaf) {
    config()->set("plausible.rate_limits.stats.{$leaf}", 'disabled');
    useHttpSnapshot('stats/aggregate');

    expect(fn () => aggregateFor('localhost'))->toThrow(
        InvalidConfigurationException::class,
        "Configuration value [plausible.rate_limits.stats.{$leaf}] must be a boolean (true/false, 1/0, on/off or yes/no), [disabled] given.",
    );
})->with(['enabled', 'adaptive', 'per_site']);

it('refuses a mistyped rate-limit switch in about, naming the full key (strict config)', function () {
    config()->set('plausible.rate_limits.sites.enabled', 'disabled');

    expect(fn () => Artisan::call('about', ['--only' => 'plausible']))->toThrow(
        InvalidConfigurationException::class,
        'Configuration value [plausible.rate_limits.sites.enabled] must be a boolean (true/false, 1/0, on/off or yes/no), [disabled] given.',
    );
});

it('refuses a mistyped tracking queue switch (strict config)', function () {
    config()->set('plausible.site', 'localhost');
    config()->set('plausible.tracking.queue', 'disabled');
    Route::middleware('plausible.track')->get('/tracked', fn () => 'ok');

    $this->withoutExceptionHandling();

    expect(fn () => $this->get('/tracked'))->toThrow(
        InvalidConfigurationException::class,
        'Configuration value [plausible.tracking.queue] must be a boolean (true/false, 1/0, on/off or yes/no), [disabled] given.',
    );
});
