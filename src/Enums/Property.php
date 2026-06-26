<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Enums;

/**
 * Properties you can break stats down by on the legacy `/api/v1/stats/breakdown`
 * endpoint. For a custom event property use {@see Property::custom()} which
 * returns the `event:props:<name>` string the API expects.
 */
enum Property: string
{
    case EventName = 'event:name';
    case EventPage = 'event:page';
    case EventHostname = 'event:hostname';
    case EventGoal = 'event:goal';
    case VisitSource = 'visit:source';
    case VisitReferrer = 'visit:referrer';
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

    /**
     * Build the property string for a custom event property.
     */
    public static function custom(string $property): string
    {
        return 'event:props:'.$property;
    }
}
