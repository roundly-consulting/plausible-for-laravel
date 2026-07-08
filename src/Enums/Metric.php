<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Enums;

use RoundlyConsulting\Enums\Helpers;

/**
 * Metrics Plausible can report. Backed by the exact keys the Stats API expects,
 * so a {@see Metric} case is interchangeable with its raw string anywhere a
 * metric is accepted.
 */
enum Metric: string
{
    use Helpers;

    case Visitors = 'visitors';
    case Visits = 'visits';
    case Pageviews = 'pageviews';
    case ViewsPerVisit = 'views_per_visit';
    case BounceRate = 'bounce_rate';
    case VisitDuration = 'visit_duration';
    case Events = 'events';
    case ScrollDepth = 'scroll_depth';
    case ConversionRate = 'conversion_rate';
    case GroupConversionRate = 'group_conversion_rate';
    case TotalRevenue = 'total_revenue';
    case AverageRevenue = 'average_revenue';
    case TimeOnPage = 'time_on_page';
    case ExitRate = 'exit_rate';
    case Percentage = 'percentage';
}
