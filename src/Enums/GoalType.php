<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Enums;

/**
 * The kind of goal created through the Sites Provisioning API: a custom event
 * goal (matched by event name) or a pageview goal (matched by page path).
 */
enum GoalType: string
{
    case Event = 'event';
    case Page = 'page';
}
