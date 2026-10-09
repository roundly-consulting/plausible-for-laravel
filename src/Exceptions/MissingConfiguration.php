<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Exceptions;

final class MissingConfiguration extends PlausibleException
{
    public static function url(): self
    {
        return new self(
            'Missing Plausible URL. Set `PLAUSIBLE_URL` (or `plausible.url`) to your '.
            'Plausible instance base URL, e.g. https://plausible.io'
        );
    }

    public static function key(): self
    {
        return new self(
            'Missing Plausible API key. The Stats and Sites APIs need one: set '.
            '`PLAUSIBLE_API_KEY` (or `plausible.key`) to an API key from your Plausible account.'
        );
    }

    public static function site(): self
    {
        return new self(
            'No site given and no default configured. Pass a site to the call or set '.
            '`PLAUSIBLE_SITE` (or `plausible.site`) to a default domain.'
        );
    }

    public static function property(): self
    {
        return new self(
            'No breakdown property given. Pass one to breakdown(), cursor() or each(), or set it '.
            'with property(), e.g. Property::VisitSource.'
        );
    }
}
