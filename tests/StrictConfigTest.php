<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use RoundlyConsulting\HttpClientRateLimits\Enums\Timespan;
use RoundlyConsulting\HttpClientRateLimits\Facades\RateLimits;
use RoundlyConsulting\PackageToolkit\Exceptions\InvalidConfigurationException;
use RoundlyConsulting\Plausible\Requests\Events\CaptureEventRequest;
use RoundlyConsulting\Plausible\Requests\Stats\AggregateRequest;
use RoundlyConsulting\Plausible\Support\PlausibleConfig;
use RoundlyConsulting\Plausible\Support\ScriptTag;

/*
 | Owner rule: a typo in a host's config or env fails loudly and never falls back silently. A
 | `per` typo used to become an hourly window, `(int)` turned a junk timeout into 0 (no
 | timeout) and a junk limit into 0, and a non-numeric max_wait/jitter was ignored. A blank
 | value (`''` or whitespace — a host's `KEY=`) is not junk: it is not set, so the default applies.
 */

/**
 * Evaluate the shipped config file with env variables set, the way a host boots it.
 *
 * @param  array<string, string>  $env
 * @return array<string, mixed>
 */
function plausibleConfigWithEnv(array $env): array
{
    foreach ($env as $name => $value) {
        $_SERVER[$name] = $value;
    }

    try {
        return require __DIR__.'/../config/plausible.php';
    } finally {
        foreach (array_keys($env) as $name) {
            unset($_SERVER[$name]);
        }
    }
}

function strictAggregate(): void
{
    plausible()->stats()->aggregate(AggregateRequest::make(site: 'localhost', metrics: ['visitors'], period: '30d'));
}

beforeEach(function (): void {
    Http::preventStrayRequests();
});

it('hands integer env values through raw (strict config)', function (): void {
    $config = plausibleConfigWithEnv([
        'PLAUSIBLE_TIMEOUT' => 'five',
        'PLAUSIBLE_RETRY_TIMES' => '2.5',
        'PLAUSIBLE_RETRY_SLEEP' => '1e3',
        'PLAUSIBLE_STATS_RATELIMIT' => 'lots',
        'PLAUSIBLE_EVENTS_RATELIMIT' => '',
        'PLAUSIBLE_SITES_RATELIMIT' => '60/h',
    ]);

    expect($config['timeout'])->toBe('five')
        ->and($config['retry']['times'])->toBe('2.5')
        ->and($config['retry']['sleep_ms'])->toBe('1e3')
        ->and($config['rate_limits']['stats']['limit'])->toBe('lots')
        ->and($config['rate_limits']['events']['limit'])->toBe('')
        ->and($config['rate_limits']['sites']['limit'])->toBe('60/h');
});

it('refuses a junk timeout or retry setting instead of casting it to 0 (strict config)', function (string $key, mixed $value): void {
    config()->set($key, $value);
    useHttpSnapshot('stats/aggregate');

    expect(fn () => strictAggregate())->toThrow(InvalidConfigurationException::class, "[{$key}]");
})->with([
    'timeout word' => ['plausible.timeout', 'five'],
    'timeout zero' => ['plausible.timeout', '0'],
    'retry times decimal' => ['plausible.retry.times', '2.5'],
    'retry times negative' => ['plausible.retry.times', -1],
]);

it('refuses a junk retry sleep when retries are on (strict config)', function (): void {
    config()->set('plausible.retry.times', 1);
    config()->set('plausible.retry.sleep_ms', 'short');
    useHttpSnapshot('stats/aggregate');

    expect(fn () => strictAggregate())->toThrow(InvalidConfigurationException::class, '[plausible.retry.sleep_ms]');
});

it('refuses a junk timeout in the health probe too (strict config)', function (): void {
    config()->set('plausible.timeout', 'five');

    expect(fn () => plausible()->health('localhost'))->toThrow(InvalidConfigurationException::class, '[plausible.timeout]');
});

it('sends with a canonical integer-string timeout (strict config)', function (): void {
    config()->set('plausible.timeout', '15');
    config()->set('plausible.retry.times', '0');
    useHttpSnapshot('stats/aggregate');

    strictAggregate();

    Http::assertSentCount(1);
    expect(PlausibleConfig::timeout())->toBe(15);
});

it('refuses a rate-limit window typo instead of an hourly window (strict config)', function (): void {
    config()->set('plausible.rate_limits.stats.per', 'hourly');
    RateLimits::fake();
    useHttpSnapshot('stats/aggregate');

    expect(fn () => strictAggregate())->toThrow(
        InvalidConfigurationException::class,
        'Configuration value [plausible.rate_limits.stats.per] must be one of [second, minute, hour, day], [hourly] given.',
    );
});

it('refuses a junk rate-limit leaf instead of casting or ignoring it (strict config)', function (string $leaf, mixed $value): void {
    config()->set("plausible.rate_limits.stats.{$leaf}", $value);
    RateLimits::fake();
    useHttpSnapshot('stats/aggregate');

    expect(fn () => strictAggregate())
        ->toThrow(InvalidConfigurationException::class, "[plausible.rate_limits.stats.{$leaf}]");
})->with([
    'limit word' => ['limit', 'lots'],
    'limit zero' => ['limit', 0],
    'max_wait word' => ['max_wait', 'never'],
    'max_wait negative' => ['max_wait', '-1'],
    'jitter decimal' => ['jitter', '0.5'],
    'owner array' => ['owner', ['app']],
]);

it('reads blank rate-limit leaves as not set, so each default applies (strict config)', function (string $blank): void {
    $settings = PlausibleConfig::rateLimit('plausible.rate_limits.stats', [
        'per' => $blank,
        'limit' => $blank,
        'owner' => $blank,
        'max_wait' => $blank,
        'jitter' => $blank,
    ], Timespan::Hour, 600);

    expect($settings)->toBe(['per' => Timespan::Hour, 'limit' => 600, 'owner' => 'app', 'max_wait' => null, 'jitter' => null]);
})->with(['empty' => '', 'whitespace' => '  ']);

it('reads blank timeout and retry settings as their defaults (strict config)', function (): void {
    config()->set('plausible.timeout', '');
    config()->set('plausible.retry.times', ' ');
    config()->set('plausible.retry.sleep_ms', '');

    expect(PlausibleConfig::timeout())->toBe(10)
        ->and(PlausibleConfig::retryTimes())->toBe(0)
        ->and(PlausibleConfig::retrySleep())->toBe(100);
});

it('keeps each surface default window and limit when the leaves are absent (strict config)', function (string $section, Timespan $per, int $limit): void {
    $settings = PlausibleConfig::rateLimit($section, [], $per, $limit);

    expect($settings)->toBe(['per' => $per, 'limit' => $limit, 'owner' => 'app', 'max_wait' => null, 'jitter' => null]);
})->with([
    'stats' => ['plausible.rate_limits.stats', Timespan::Hour, 600],
    'events' => ['plausible.rate_limits.events', Timespan::Minute, 6000],
]);

it('paces the events surface per minute when its window is absent (strict config)', function (): void {
    config()->set('plausible.rate_limits.events.per', null);
    config()->set('plausible.rate_limits.events.limit', null);
    $fake = RateLimits::fake();
    useHttpSnapshot('events/capture');

    plausible()->events()
        ->setVisitor('PestAgent', '127.0.0.1')
        ->capture(CaptureEventRequest::make(site: 'localhost', url: 'https://localhost/', name: 'pageview'));

    $fake->assertAllowed('plausible:events:app:localhost:localhost');
});

it('reads canonical integer-string rate-limit leaves (strict config)', function (): void {
    $settings = PlausibleConfig::rateLimit('plausible.rate_limits.stats', [
        'per' => 'day',
        'limit' => '300',
        'owner' => 'fleet',
        'max_wait' => '0',
        'jitter' => '250',
    ], Timespan::Hour, 600);

    expect($settings)->toBe(['per' => Timespan::Day, 'limit' => 300, 'owner' => 'fleet', 'max_wait' => 0, 'jitter' => 250]);
});

it('refuses a wrong-typed tracking identity (strict config)', function (string $key, mixed $value): void {
    config()->set($key, $value);

    $read = $key === 'plausible.tracking.user_agent'
        ? fn () => PlausibleConfig::trackingUserAgent()
        : fn () => PlausibleConfig::trackingIpAddress();

    expect($read)->toThrow(InvalidConfigurationException::class, "[{$key}]");
})->with([
    'user agent int' => ['plausible.tracking.user_agent', 5],
    'ip array' => ['plausible.tracking.ip_address', ['127.0.0.1']],
]);

it('reads a blank tracking identity or script url as its default (strict config)', function (string $blank): void {
    config()->set('plausible.tracking.user_agent', $blank);
    config()->set('plausible.tracking.ip_address', $blank);
    config()->set('plausible.script.src', $blank);

    expect(PlausibleConfig::trackingUserAgent())->toBe('PlausibleForLaravel')
        ->and(PlausibleConfig::trackingIpAddress())->toBe('127.0.0.1')
        ->and(PlausibleConfig::scriptSrc())->toBe('https://plausible.io/js/script.js');
})->with(['empty' => '', 'whitespace' => '  ']);

it('refuses a wrong-typed script domain instead of deferring to the site (strict config)', function (): void {
    config()->set('plausible.script.domain', ['example.com']);
    config()->set('plausible.site', 'site.test');

    expect(fn () => ScriptTag::render())->toThrow(InvalidConfigurationException::class, '[plausible.script.domain]');
});

it('defers a blank script domain to the site (strict config)', function (string $blank): void {
    config()->set('plausible.script.domain', $blank);
    config()->set('plausible.site', 'site.test');

    expect((string) ScriptTag::render())->toContain('data-domain="site.test"');
})->with(['empty' => '', 'whitespace' => '  ']);

it('reads a blank url, key or site as not configured (strict config)', function (): void {
    config()->set('plausible.url', '  ');
    config()->set('plausible.key', '  ');
    config()->set('plausible.site', '  ');

    $health = plausible()->health();

    expect($health->hasUrl)->toBeFalse()
        ->and($health->hasKey)->toBeFalse()
        ->and((string) ScriptTag::render())->toBe('');
});
