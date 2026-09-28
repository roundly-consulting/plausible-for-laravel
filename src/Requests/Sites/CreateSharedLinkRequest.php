<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Requests\Sites;

use RoundlyConsulting\Plausible\Requests\Query;

/**
 * Find or create a shared dashboard link via the Sites Provisioning API. Plausible's
 * endpoint takes only `site_id` and `name`; it offers no password protection.
 */
final class CreateSharedLinkRequest
{
    public function __construct(
        public readonly string $site,
        public readonly string $name,
    ) {}

    public static function make(string $site, string $name): self
    {
        return new self($site, $name);
    }

    public function toQuery(): Query
    {
        return Query::make()
            ->set('site_id', $this->site)
            ->set('name', $this->name);
    }
}
