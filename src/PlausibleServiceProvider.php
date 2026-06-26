<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use RoundlyConsulting\Plausible\Commands\HealthCommand;
use RoundlyConsulting\Plausible\Http\Middleware\TrackPageview;
use RoundlyConsulting\Plausible\View\Components\PlausibleScript;

final class PlausibleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/plausible.php', 'plausible');

        $this->app->singleton(Plausible::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'plausible');

        $this->registerBladeIntegration();
        $this->registerMiddleware();

        if ($this->app->runningInConsole()) {
            $this->commands([
                HealthCommand::class,
            ]);

            $this->publishes([
                __DIR__.'/../config/plausible.php' => config_path('plausible.php'),
            ], 'plausible-config');

            $this->publishes([
                __DIR__.'/../resources/views' => $this->app->resourcePath('views/vendor/plausible'),
            ], 'plausible-views');
        }
    }

    private function registerBladeIntegration(): void
    {
        Blade::directive('plausibleScript', static fn (string $expression): string => "<?php echo \RoundlyConsulting\Plausible\Support\ScriptTag::render({$expression}); ?>");

        Blade::component('plausible-script', PlausibleScript::class);
    }

    private function registerMiddleware(): void
    {
        $router = $this->app->make(Router::class);

        $router->aliasMiddleware('plausible.track', TrackPageview::class);
    }
}
