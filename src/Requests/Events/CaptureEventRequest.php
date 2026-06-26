<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Requests\Events;

use RoundlyConsulting\Plausible\DataTransferObjects\Revenue;
use RoundlyConsulting\Plausible\Requests\Query;

final class CaptureEventRequest
{
    /**
     * @param  array<string, mixed>  $props
     */
    public function __construct(
        public readonly string $site,
        public readonly string $url,
        public readonly string $name = 'pageview',
        public readonly ?string $referrer = null,
        public readonly array $props = [],
        public readonly ?Revenue $revenue = null,
        public readonly bool $interactive = true,
    ) {}

    /**
     * @param  array<string, mixed>  $props
     */
    public static function make(
        string $site,
        string $url,
        string $name = 'pageview',
        ?string $referrer = null,
        array $props = [],
        ?Revenue $revenue = null,
        bool $interactive = true,
    ): self {
        return new self($site, $url, $name, $referrer, $props, $revenue, $interactive);
    }

    public function hasCustomProps(): bool
    {
        return count($this->props) > 0;
    }

    public function hasReferrer(): bool
    {
        return ! is_null($this->referrer);
    }

    public function hasRevenue(): bool
    {
        return $this->revenue instanceof Revenue;
    }

    public function toQuery(): Query
    {
        return Query::make()
            ->set('domain', $this->site)
            ->set('name', $this->name)
            ->set('url', $this->url)
            ->when($this->hasReferrer(), 'referrer', $this->referrer)
            ->when($this->hasCustomProps(), 'props', $this->props)
            ->when($this->hasRevenue(), 'revenue', fn (): array => $this->revenue?->toArray() ?? [])
            ->when(! $this->interactive, 'interactive', false);
    }
}
