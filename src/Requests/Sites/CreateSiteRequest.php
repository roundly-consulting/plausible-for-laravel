<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Requests\Sites;

use RoundlyConsulting\Plausible\Requests\Query;

final class CreateSiteRequest
{
    public function __construct(
        public readonly string $domain,
        public readonly string $timezone = 'Etc/UTC',
    ) {}

    public static function make(
        string $domain,
        string $timezone = 'Etc/UTC',
    ): self {
        return new self($domain, $timezone);
    }

    public function toQuery(): Query
    {
        return Query::make()
            ->set('domain', $this->domain)
            ->set('timezone', $this->timezone);
    }
}
