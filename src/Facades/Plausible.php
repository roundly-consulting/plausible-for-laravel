<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Facades;

use Illuminate\Support\Facades\Facade;
use RoundlyConsulting\Plausible\Plausible as PlausibleManager;

/**
 * @method static \RoundlyConsulting\Plausible\Repositories\StatsRepository stats()
 * @method static \RoundlyConsulting\Plausible\Repositories\EventsRepository events()
 * @method static \RoundlyConsulting\Plausible\Repositories\SitesRepository sites()
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
