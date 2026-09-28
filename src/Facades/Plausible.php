<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Facades;

use Illuminate\Support\Facades\Facade;
use RoundlyConsulting\Plausible\PlausibleManager;
use RoundlyConsulting\Plausible\Testing\PlausibleFake;

/**
 * @method static \RoundlyConsulting\Plausible\Repositories\StatsRepository stats()
 * @method static \RoundlyConsulting\Plausible\Repositories\EventsRepository events()
 * @method static \RoundlyConsulting\Plausible\Repositories\SitesRepository sites()
 * @method static \RoundlyConsulting\Plausible\Repositories\GoalsRepository goals()
 * @method static \RoundlyConsulting\Plausible\Repositories\SharedLinksRepository sharedLinks()
 * @method static \Illuminate\Support\HtmlString script(?string $domain = null)
 * @method static bool ping(?string $site = null)
 * @method static \RoundlyConsulting\Plausible\Results\HealthResult health(?string $site = null)
 * @method static void assertEventCaptured(?callable $filter = null)
 * @method static void assertEventNotCaptured(?callable $filter = null)
 * @method static void assertNothingCaptured()
 * @method static void assertEventCapturedCount(int $count)
 * @method static void assertQueried(?callable $filter = null)
 * @method static void assertNothingQueried()
 * @method static void assertSiteCreated(?string $domain = null)
 * @method static void assertSiteNotCreated(?string $domain = null)
 * @method static void assertSiteDeleted(string $domain)
 * @method static void assertSiteNotDeleted(?string $domain = null)
 * @method static void assertSiteUpdated(string $domain)
 * @method static void assertSiteNotUpdated(?string $domain = null)
 * @method static void assertGoalCreated(?string $site = null)
 * @method static void assertGoalNotCreated(?string $site = null)
 * @method static void assertGoalDeleted(string $site, int|string $goalId)
 * @method static void assertGoalNotDeleted(?string $site = null, int|string|null $goalId = null)
 * @method static void assertSharedLinkCreated(?string $site = null)
 * @method static void assertSharedLinkNotCreated(?string $site = null)
 *
 * @see PlausibleManager
 */
final class Plausible extends Facade
{
    /**
     * Swap the manager for a recording fake — behind the facade and in the container, so an
     * injected PlausibleManager, `plausible()`, the `Trackable` trait and the `TrackPageview`
     * middleware are faked too.
     */
    public static function fake(): PlausibleFake
    {
        $fake = app()->make(PlausibleFake::class);

        self::swap($fake);

        return $fake;
    }

    protected static function getFacadeAccessor(): string
    {
        return PlausibleManager::class;
    }
}
