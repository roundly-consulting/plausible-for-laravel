<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Enums;

/**
 * Dimensions accepted by the Stats v2 `/api/v2/query` endpoint. Includes the
 * `time:*` bucket dimensions used in place of the legacy `interval` parameter.
 * For a custom event property use {@see Dimension::custom()}.
 */
enum Dimension: string
{
    case EventName = 'event:name';
    case EventPage = 'event:page';
    case EventHostname = 'event:hostname';
    case EventGoal = 'event:goal';
    case VisitSource = 'visit:source';
    case VisitReferrer = 'visit:referrer';
    case VisitChannel = 'visit:channel';
    case VisitUtmMedium = 'visit:utm_medium';
    case VisitUtmSource = 'visit:utm_source';
    case VisitUtmCampaign = 'visit:utm_campaign';
    case VisitUtmContent = 'visit:utm_content';
    case VisitUtmTerm = 'visit:utm_term';
    case VisitDevice = 'visit:device';
    case VisitBrowser = 'visit:browser';
    case VisitBrowserVersion = 'visit:browser_version';
    case VisitOs = 'visit:os';
    case VisitOsVersion = 'visit:os_version';
    case VisitCountry = 'visit:country';
    case VisitRegion = 'visit:region';
    case VisitCity = 'visit:city';
    case VisitEntryPage = 'visit:entry_page';
    case VisitExitPage = 'visit:exit_page';
    case TimeHour = 'time:hour';
    case TimeDay = 'time:day';
    case TimeWeek = 'time:week';
    case TimeMonth = 'time:month';

    /**
     * Build the dimension string for a custom event property.
     */
    public static function custom(string $property): string
    {
        return 'event:props:'.$property;
    }
}
