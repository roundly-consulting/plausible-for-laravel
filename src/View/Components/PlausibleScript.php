<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\View\Components;

use Illuminate\Support\HtmlString;
use Illuminate\View\Component;
use RoundlyConsulting\Plausible\PlausibleManager;

/**
 * Renders the hosted Plausible tracking script: `<x-plausible-script />`.
 * Pass `:domain` to override the configured site/domain. Renders
 * `Plausible::script()` — the publishable `plausible::components.script` view — so a
 * host override of the manager or a published view applies here too.
 */
final class PlausibleScript extends Component
{
    public function __construct(
        public ?string $domain = null,
    ) {}

    public function render(): HtmlString
    {
        return app(PlausibleManager::class)->script($this->domain);
    }
}
