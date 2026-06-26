<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Facades;

use Illuminate\Support\Facades\Facade;
use RoundlyConsulting\Plausible\Plausible as PlausibleManager;

/**
 * @method static \RoundlyConsulting\Plausible\Repositories\StatsRepository stats()
 * @method static \RoundlyConsulting\Plausible\Repositories\EventsRepository events()
 * @method static \RoundlyConsulting\Plausible\Repositories\SitesRepository sites()
 * @method static \RoundlyConsulting\Plausible\Repositories\GoalsRepository goals()
 * @method static \RoundlyConsulting\Plausible\Repositories\SharedLinksRepository sharedLinks()
 * @method static bool ping()
 * @method static \RoundlyConsulting\Plausible\Results\HealthResult health()
 * @method static \RoundlyConsulting\Plausible\Testing\PlausibleFake fake()
 * @method static void recordEvent(\RoundlyConsulting\Plausible\Requests\Events\CaptureEventRequest $request)
 * @method static void assertEventCaptured(?callable $filter = null)
 * @method static void assertEventNotCaptured(?callable $filter = null)
 * @method static void assertNothingCaptured()
 * @method static void assertEventCapturedCount(int $count)
 * @method static void assertQueried(?callable $filter = null)
 * @method static void assertNothingQueried()
 * @method static void assertSiteCreated(?string $domain = null)
 * @method static void assertSiteDeleted(string $domain)
 * @method static void assertSiteUpdated(string $domain)
 * @method static void assertGoalCreated(?string $site = null)
 * @method static void assertGoalDeleted(string $site, int|string $goalId)
 * @method static void assertSharedLinkCreated(?string $site = null)
 *
 * @see PlausibleManager
 */
final class Plausible extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return PlausibleManager::class;
    }
}
