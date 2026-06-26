<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible;

use Illuminate\Support\ServiceProvider;

final class PlausibleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/plausible.php', 'plausible');

        $this->app->singleton(Plausible::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/plausible.php' => config_path('plausible.php'),
            ], 'plausible-config');
        }
    }
}
