<?php

declare(strict_types=1);

use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\ServiceProvider;
use RoundlyConsulting\Plausible\Http\Middleware\TrackPageview;
use RoundlyConsulting\Plausible\PlausibleManager;
use RoundlyConsulting\Plausible\PlausibleServiceProvider;

it('merges the package config', function (): void {
    expect(config('plausible.script.src'))->toBe('https://plausible.io/js/script.js')
        ->and(config('plausible.rate_limits.stats.per'))->toBe('hour');
});

it('binds the client as a singleton', function (): void {
    expect(app(PlausibleManager::class))->toBe(app(PlausibleManager::class));
});

it('registers the package commands', function (): void {
    expect(Artisan::all())->toHaveKey('plausible:health');
});

it('registers the package views under the plausible namespace', function (): void {
    expect(app(ViewFactory::class)->exists('plausible::components.script'))->toBeTrue();
});

it('registers the tracking middleware alias', function (): void {
    expect(app(Router::class)->getMiddleware())
        ->toHaveKey('plausible.track', TrackPageview::class);
});

it('publishes the config under the plausible-config tag', function (): void {
    $paths = ServiceProvider::pathsToPublish(PlausibleServiceProvider::class, 'plausible-config');

    expect($paths)->toHaveCount(1)
        ->and(array_values($paths)[0])->toEndWith('config/plausible.php');
});

it('publishes the views under the plausible-views tag', function (): void {
    $paths = ServiceProvider::pathsToPublish(PlausibleServiceProvider::class, 'plausible-views');

    expect($paths)->toHaveCount(1)
        ->and(array_values($paths)[0])->toEndWith('views/vendor/plausible');
});

it('contributes a section to the about command', function (): void {
    $this->artisan('about', ['--only' => 'plausible'])
        ->expectsOutputToContain('http://localhost:8000')
        ->expectsOutputToContain('stats, events, sites')
        ->assertSuccessful();
});

it('reports the disabled surfaces to the about command', function (): void {
    config()->set('plausible.rate_limits.stats.enabled', false);
    config()->set('plausible.rate_limits.events.enabled', false);
    config()->set('plausible.rate_limits.sites.enabled', false);
    config()->set('plausible.key', null);
    config()->set('plausible.site', null);

    $this->artisan('about', ['--only' => 'plausible'])
        ->expectsOutputToContain('MISSING')
        ->expectsOutputToContain('NONE')
        ->expectsOutputToContain('OFF')
        ->assertSuccessful();
});
