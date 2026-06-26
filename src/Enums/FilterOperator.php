<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Enums;

/**
 * Operators for the array-style filters used by the Stats v2 `/api/v2/query`
 * endpoint, e.g. `['is', 'visit:browser', ['Chrome']]`.
 */
enum FilterOperator: string
{
    case Is = 'is';
    case IsNot = 'is_not';
    case Contains = 'contains';
    case ContainsNot = 'contains_not';
    case Matches = 'matches';
    case MatchesNot = 'matches_not';
    case And_ = 'and';
    case Or_ = 'or';
    case Not = 'not';
    case HasDone = 'has_done';
    case HasNotDone = 'has_not_done';
}
