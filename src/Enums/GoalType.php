<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Enums;

use RoundlyConsulting\Enums\Helpers;

/**
 * The kind of goal created through the Sites Provisioning API: a custom event
 * goal (matched by event name) or a pageview goal (matched by page path).
 */
enum GoalType: string
{
    use Helpers;

    case Event = 'event';
    case Page = 'page';
}
