<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Concerns;

use Illuminate\Database\Eloquent\Model;
use ReflectionClass;
use RoundlyConsulting\PackageToolkit\Support\Config;
use RoundlyConsulting\Plausible\Requests\Events\CaptureEventRequest;
use RoundlyConsulting\Plausible\Support\PlausibleConfig;

/**
 * Emits Plausible events on Eloquent lifecycle hooks. Configure per model with a
 * `$plausibleEvents` map of hook => event name, e.g.
 *
 *     protected array $plausibleEvents = ['created' => 'Order Placed'];
 *
 * Override {@see plausibleEventProps()} / {@see plausibleEventUrl()} to customise
 * the payload. Captures are queued by default (see `plausible.tracking.queue`).
 *
 * @mixin Model
 */
trait Trackable
{
    public static function bootTrackable(): void
    {
        foreach (static::plausibleEventMap() as $hook => $eventName) {
            static::registerModelEvent($hook, static function (Model $model) use ($eventName): void {
                if (method_exists($model, 'dispatchPlausibleEvent')) {
                    $model->dispatchPlausibleEvent($eventName);
                }
            });
        }
    }

    /**
     * Map of Eloquent hook (created/updated/deleted/…) to Plausible event name,
     * read from the model's `$plausibleEvents` default without instantiating it
     * (boot runs before the model can be constructed).
     *
     * @return array<string, string>
     */
    protected static function plausibleEventMap(): array
    {
        $configured = (new ReflectionClass(static::class))->getDefaultProperties()['plausibleEvents'] ?? [];
        $events = [];

        if (is_array($configured)) {
            foreach ($configured as $hook => $name) {
                if (is_string($hook) && is_string($name)) {
                    $events[$hook] = $name;
                }
            }
        }

        return $events;
    }

    /**
     * Build the custom properties sent with the event for this model.
     *
     * @return array<string, mixed>
     */
    protected function plausibleEventProps(string $event): array
    {
        return [];
    }

    /**
     * The URL attributed to the event. Defaults to the app URL plus the model's
     * table and key, so events land on a stable, model-specific path.
     */
    protected function plausibleEventUrl(string $event): string
    {
        return rtrim((string) config('app.url'), '/').'/'.$this->getTable().'/'.$this->getKey();
    }

    protected function plausibleEventSite(): ?string
    {
        return PlausibleConfig::present(config('plausible.site'));
    }

    public function dispatchPlausibleEvent(string $name): void
    {
        $site = $this->plausibleEventSite();

        if ($site === null) {
            return;
        }

        [$userAgent, $ipAddress] = $this->plausibleVisitor();

        $events = plausible()->events()->setVisitor($userAgent, $ipAddress);

        $request = CaptureEventRequest::make(
            site: $site,
            url: $this->plausibleEventUrl($name),
            name: $name,
            props: $this->plausibleEventProps($name),
        );

        if (Config::boolean('plausible.tracking.queue', true)) {
            $events->captureQueued($request);

            return;
        }

        $events->capture($request);
    }

    /**
     * Resolve the visitor identifiers: the current request's when available,
     * otherwise the configured server-side defaults.
     *
     * @return array{0: string, 1: string}
     */
    protected function plausibleVisitor(): array
    {
        $request = request();
        $userAgent = $request->userAgent();
        $ipAddress = $request->ip();

        if (is_string($userAgent) && $userAgent !== '' && is_string($ipAddress) && $ipAddress !== '') {
            return [$userAgent, $ipAddress];
        }

        return [
            PlausibleConfig::trackingUserAgent(),
            PlausibleConfig::trackingIpAddress(),
        ];
    }
}
