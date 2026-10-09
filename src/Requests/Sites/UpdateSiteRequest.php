<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Requests\Sites;

use RoundlyConsulting\Plausible\Exceptions\UnsupportedRequest;
use RoundlyConsulting\Plausible\Requests\Query;

/**
 * Update an existing site. `site` is the current domain (the id in the URL) and `domain`
 * the new one. Plausible's `PUT /api/v1/sites/:site_id` changes only the domain, so a
 * `timezone` (still accepted as a parameter) or a missing domain is refused before
 * anything is sent.
 */
final class UpdateSiteRequest
{
    /**
     * @throws UnsupportedRequest when a timezone is given, or no new domain
     */
    public function __construct(
        public readonly string $site,
        public readonly ?string $domain = null,
        public readonly ?string $timezone = null,
    ) {
        if ($timezone !== null) {
            throw UnsupportedRequest::siteTimezone();
        }

        if ($domain === null) {
            throw UnsupportedRequest::emptySiteUpdate();
        }
    }

    /**
     * @throws UnsupportedRequest when a timezone is given, or no new domain
     */
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
            ->when(! is_null($this->domain), 'domain', $this->domain);
    }
}
