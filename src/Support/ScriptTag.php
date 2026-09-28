<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Support;

use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Support\HtmlString;

/**
 * Builds the official hosted Plausible tracking-script tag from config. No JS is
 * bundled — this renders the `plausible::components.script` view (publishable with
 * the `plausible-views` tag) with the resolved `$domain` and `$src`. Host code calls
 * `Plausible::script()`, which the `@plausibleScript` directive and the
 * `<x-plausible-script>` component render too.
 *
 * @internal
 */
final class ScriptTag
{
    public static function render(?string $domain = null): HtmlString
    {
        $domain ??= self::configuredDomain();

        if ($domain === null || $domain === '') {
            return new HtmlString('');
        }

        $src = config('plausible.script.src', 'https://plausible.io/js/script.js');
        $src = is_string($src) && $src !== '' ? $src : 'https://plausible.io/js/script.js';

        return new HtmlString(trim(app(ViewFactory::class)->make('plausible::components.script', [
            'domain' => $domain,
            'src' => $src,
        ])->render()));
    }

    private static function configuredDomain(): ?string
    {
        $domain = config('plausible.script.domain');

        if (is_string($domain) && $domain !== '') {
            return $domain;
        }

        $site = config('plausible.site');

        return is_string($site) && $site !== '' ? $site : null;
    }
}
