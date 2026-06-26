<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Requests\Sites;

use RoundlyConsulting\Plausible\Requests\Query;

/**
 * Create (or find) a shared dashboard link via the Sites Provisioning API.
 * Provide an optional `password` to protect the link.
 */
final class CreateSharedLinkRequest
{
    public function __construct(
        public readonly string $site,
        public readonly string $name,
        public readonly ?string $password = null,
    ) {}

    public static function make(string $site, string $name, ?string $password = null): self
    {
        return new self($site, $name, $password);
    }

    public function toQuery(): Query
    {
        return Query::make()
            ->set('site_id', $this->site)
            ->set('name', $this->name)
            ->when($this->password !== null, 'password', $this->password);
    }
}
