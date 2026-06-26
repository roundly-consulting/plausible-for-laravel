<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Requests\Sites;

use RoundlyConsulting\Plausible\Enums\GoalType;
use RoundlyConsulting\Plausible\Requests\Query;
use RoundlyConsulting\Plausible\Support\Value;

/**
 * Create (or find) a goal via the Sites Provisioning API. Provide `eventName`
 * for an event goal or `pagePath` for a pageview goal.
 */
final class CreateGoalRequest
{
    public function __construct(
        public readonly string $site,
        public readonly string|GoalType $goalType,
        public readonly ?string $eventName = null,
        public readonly ?string $pagePath = null,
    ) {}

    public static function event(string $site, string $eventName): self
    {
        return new self($site, GoalType::Event, eventName: $eventName);
    }

    public static function page(string $site, string $pagePath): self
    {
        return new self($site, GoalType::Page, pagePath: $pagePath);
    }

    public function toQuery(): Query
    {
        return Query::make()
            ->set('site_id', $this->site)
            ->set('goal_type', Value::of($this->goalType))
            ->when($this->eventName !== null, 'event_name', $this->eventName)
            ->when($this->pagePath !== null, 'page_path', $this->pagePath);
    }
}
