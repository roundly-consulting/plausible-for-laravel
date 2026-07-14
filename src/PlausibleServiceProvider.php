<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use RoundlyConsulting\PackageToolkit\Package;
use RoundlyConsulting\PackageToolkit\PackageServiceProvider;
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
                    'Queued tracking' => config('plausible.tracking.queue', true) !== false ? 'ON' : 'OFF',
                ];
            });
    }

    public function register(): void
    {
        parent::register();

        $this->app->singleton(Plausible::class);
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

        $enabled = array_keys(array_filter(
            $surfaces,
            static fn (array $surface): bool => ($surface['enabled'] ?? true) !== false,
        ));

        return $enabled === [] ? 'OFF' : implode(', ', $enabled);
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
