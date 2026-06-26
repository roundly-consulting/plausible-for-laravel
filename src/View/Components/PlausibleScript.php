<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\View\Components;

use Illuminate\Support\HtmlString;
use Illuminate\View\Component;
use RoundlyConsulting\Plausible\Support\ScriptTag;

/**
 * Renders the hosted Plausible tracking script: `<x-plausible-script />`.
 * Pass `:domain` to override the configured site/domain.
 */
final class PlausibleScript extends Component
{
    public function __construct(
        public ?string $domain = null,
    ) {}

    public function render(): HtmlString
    {
        return ScriptTag::render($this->domain);
    }
}
