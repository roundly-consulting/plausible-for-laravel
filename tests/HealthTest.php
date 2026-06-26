<?php

declare(strict_types=1);

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RoundlyConsulting\Plausible\Results\HealthResult;

it('reports healthy when reachable and authorized', function () {
    Http::fake(['*' => Http::response(['sites' => []], 200)]);

    $health = plausible()->health();

    expect($health)->toBeInstanceOf(HealthResult::class)
        ->and($health->ok())->toBeTrue()
        ->and($health->isConfigured())->toBeTrue()
        ->and($health->status)->toBe(200)
        ->and(plausible()->ping())->toBeTrue();
});

it('reports a rejected api key', function () {
    Http::fake(['*' => Http::response('', 401)]);

    $health = plausible()->health();

    expect($health->reachable)->toBeFalse()
        ->and($health->status)->toBe(401)
        ->and($health->ok())->toBeFalse();
});

it('reports reachable for other non-success statuses', function () {
    Http::fake(['*' => Http::response('', 402)]);

    $health = plausible()->health();

    expect($health->reachable)->toBeTrue()->and($health->status)->toBe(402);
});

it('reports unconfigured when the url is missing', function () {
    config()->set('plausible.url', null);

    $health = plausible()->health();

    expect($health->isConfigured())->toBeFalse()
        ->and($health->reachable)->toBeFalse()
        ->and($health->status)->toBeNull();
});

it('reports unreachable on a connection failure', function () {
    Http::fake(fn () => throw new ConnectionException('down'));

    expect(plausible()->ping())->toBeFalse();
});

it('passes the health command when reachable', function () {
    Http::fake(['*' => Http::response(['sites' => []], 200)]);

    $this->artisan('plausible:health')->assertSuccessful();
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
