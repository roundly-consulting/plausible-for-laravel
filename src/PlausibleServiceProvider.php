<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use RoundlyConsulting\PackageToolkit\Package;
use RoundlyConsulting\PackageToolkit\PackageServiceProvider;
use RoundlyConsulting\PackageToolkit\Support\Config;
use RoundlyConsulting\Plausible\Commands\HealthCommand;
use RoundlyConsulting\Plausible\Http\Middleware\TrackPageview;
use RoundlyConsulting\Plausible\View\Components\PlausibleScript;

final class PlausibleServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('plausible')
            ->hasConfigFile()
            ->hasViews()
            ->hasCommands([
                HealthCommand::class,
            ])
            ->contributesToAbout(static function (): array {
                $url = config('plausible.url');
                $site = config('plausible.site');
                $key = config('plausible.key');

                return [
                    'Instance' => is_string($url) && $url !== '' ? $url : 'NOT SET',
                    'API key' => is_string($key) && $key !== '' ? 'SET' : 'MISSING',
                    'Default site' => is_string($site) && $site !== '' ? $site : 'NONE',
                    'Rate limiting' => self::throttledSurfaces(),
                    'Queued tracking' => Config::boolean('plausible.tracking.queue', true) ? 'ON' : 'OFF',
                ];
            });
    }

    public function register(): void
    {
        parent::register();

        $this->app->singleton(PlausibleManager::class);
    }

    public function boot(): void
    {
        parent::boot();

        $this->registerBladeIntegration();
        $this->registerMiddleware();
    }

    /**
     * The API surfaces still paced by the client-side limiter, so `about` shows
     * at a glance which buckets a host has switched off.
     */
    private static function throttledSurfaces(): string
    {
        /** @var array<string, array<string, mixed>> $surfaces */
        $surfaces = config('plausible.rate_limits', []);

        // Wrapped under its real key so a bad value is reported as
        // `plausible.rate_limits.{surface}.enabled`, not as a bare `enabled`.
        $enabled = array_keys(array_filter(
            $surfaces,
            static fn (array $surface, string $name): bool => Config::for(["plausible.rate_limits.{$name}.enabled" => $surface['enabled'] ?? null])
                ->boolean("plausible.rate_limits.{$name}.enabled", true),
            ARRAY_FILTER_USE_BOTH,
        ));

        return $enabled === [] ? 'OFF' : implode(', ', $enabled);
    }

    private function registerBladeIntegration(): void
    {
        Blade::directive('plausibleScript', static fn (string $expression): string => "<?php echo app(\RoundlyConsulting\Plausible\PlausibleManager::class)->script({$expression}); ?>");

        Blade::component('plausible-script', PlausibleScript::class);
    }

    private function registerMiddleware(): void
    {
        $router = $this->app->make(Router::class);

        $router->aliasMiddleware('plausible.track', TrackPageview::class);
    }
}
