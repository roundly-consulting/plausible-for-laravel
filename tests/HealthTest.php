<?php

declare(strict_types=1);

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RoundlyConsulting\Plausible\Results\HealthResult;

it('reports healthy when the sites api accepts the key', function () {
    Http::fake(['*' => Http::response(['sites' => []], 200)]);

    $health = plausible()->health();

    expect($health)->toBeInstanceOf(HealthResult::class)
        ->and($health->ok())->toBeTrue()
        ->and($health->isConfigured())->toBeTrue()
        ->and($health->reachable)->toBeTrue()
        ->and($health->authorized)->toBeTrue()
        ->and($health->status)->toBe(200)
        ->and(plausible()->ping())->toBeTrue();
});

it('probes the stats api for the default site, which a stats-scoped key can reach', function () {
    config()->set('plausible.site', 'my-site.com');
    Http::fake(['*' => Http::response('7', 200)]);

    $health = plausible()->health();

    expect($health->ok())->toBeTrue()
        ->and($health->message)->toContain('my-site.com');

    Http::assertSent(fn ($request) => str_contains($request->url(), '/api/v1/stats/realtime/visitors')
        && $request['site_id'] === 'my-site.com'
        && $request->hasHeader('Authorization', 'Bearer test-api-key'));
    Http::assertNotSent(fn ($request) => str_contains($request->url(), '/api/v1/sites'));
});

it('probes the stats api for an explicit site', function () {
    config()->set('plausible.site', 'default.test');
    Http::fake(['*' => Http::response('0', 200)]);

    expect(plausible()->ping('other.test'))->toBeTrue();

    Http::assertSent(fn ($request) => $request['site_id'] === 'other.test');
});

it('fails on a server error', function (int $status) {
    Http::fake(['*' => Http::response('', $status)]);

    $health = plausible()->health();

    expect($health->ok())->toBeFalse()
        ->and($health->reachable)->toBeFalse()
        ->and($health->status)->toBe($status)
        ->and($health->message)->toContain("HTTP {$status}")
        ->and(plausible()->ping())->toBeFalse();
})->with([500, 502, 503]);

it('fails when the api endpoint is not found', function () {
    Http::fake(['*' => Http::response('', 404)]);

    $health = plausible()->health();

    expect($health->ok())->toBeFalse()
        ->and($health->reachable)->toBeFalse()
        ->and($health->message)->toContain('PLAUSIBLE_URL');
});

it('reports a key rejected for the site', function () {
    config()->set('plausible.site', 'my-site.com');
    Http::fake(['*' => Http::response('', 401)]);

    $health = plausible()->health();

    expect($health->ok())->toBeFalse()
        ->and($health->reachable)->toBeTrue()
        ->and($health->authorized)->toBeFalse()
        ->and($health->status)->toBe(401)
        ->and($health->message)->toContain('rejected the API key for my-site.com');
});

it('explains the key scope when the sites api refuses the key', function (int $status) {
    Http::fake(['*' => Http::response('', $status)]);

    $health = plausible()->health();

    expect($health->ok())->toBeFalse()
        ->and($health->reachable)->toBeTrue()
        ->and($health->authorized)->toBeFalse()
        ->and($health->status)->toBe($status)
        ->and($health->message)->toContain('Sites API')
        ->toContain('PLAUSIBLE_SITE');
})->with([401, 402, 403]);

it('does not pass a rate-limited probe', function () {
    Http::fake(['*' => Http::response('', 429)]);

    $health = plausible()->health();

    expect($health->ok())->toBeFalse()
        ->and($health->reachable)->toBeTrue()
        ->and($health->message)->toContain('HTTP 429');
});

it('does not pass another client error', function () {
    Http::fake(['*' => Http::response('', 400)]);

    $health = plausible()->health();

    expect($health->ok())->toBeFalse()
        ->and($health->reachable)->toBeTrue()
        ->and($health->status)->toBe(400);
});

it('reports unconfigured when the url is missing', function () {
    config()->set('plausible.url', null);

    $health = plausible()->health();

    expect($health->isConfigured())->toBeFalse()
        ->and($health->reachable)->toBeFalse()
        ->and($health->authorized)->toBeFalse()
        ->and($health->status)->toBeNull();
});

it('reports unreachable on a connection failure', function () {
    Http::fake(fn () => throw new ConnectionException('down'));

    expect(plausible()->ping())->toBeFalse()
        ->and(plausible()->health()->reachable)->toBeFalse();
});

it('passes the health command when reachable', function () {
    Http::fake(['*' => Http::response(['sites' => []], 200)]);

    $this->artisan('plausible:health')
        ->expectsOutputToContain('Key accepted:   yes')
        ->assertSuccessful();
});

it('fails the health command on a server error', function () {
    Http::fake(['*' => Http::response('', 500)]);

    $this->artisan('plausible:health')
        ->expectsOutputToContain('HTTP 500')
        ->assertFailed();
});

it('verifies a given site from the health command', function () {
    Http::fake(['*' => Http::response('3', 200)]);

    $this->artisan('plausible:health', ['--site' => 'cli.test'])->assertSuccessful();

    Http::assertSent(fn ($request) => $request['site_id'] === 'cli.test');
});

it('fails the health command when the key is rejected', function () {
    Http::fake(['*' => Http::response('', 401)]);

    $this->artisan('plausible:health')->assertFailed();
});

it('shows the missing key in the command output', function () {
    config()->set('plausible.key', null);

    $this->artisan('plausible:health')
        ->expectsOutputToContain('Key configured: no')
        ->assertFailed();
});
