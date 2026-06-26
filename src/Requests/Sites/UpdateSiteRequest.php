<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Requests\Sites;

use RoundlyConsulting\Plausible\Requests\Query;

/**
 * Update an existing site. `site` is the current domain (the id in the URL);
 * the optional fields below are the changes to apply.
 */
final class UpdateSiteRequest
{
    public function __construct(
        public readonly string $site,
        public readonly ?string $domain = null,
        public readonly ?string $timezone = null,
    ) {}

    public static function make(
        string $site,
        ?string $domain = null,
        ?string $timezone = null,
    ): self {
        return new self($site, $domain, $timezone);
    }

    public function toQuery(): Query
    {
        return Query::make()
            ->when(! is_null($this->domain), 'domain', $this->domain)
            ->when(! is_null($this->timezone), 'timezone', $this->timezone);
    }
}
