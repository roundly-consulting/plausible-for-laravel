<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible;

use Illuminate\Support\Facades\Facade;
use RoundlyConsulting\Plausible\Repositories\EventsRepository;
use RoundlyConsulting\Plausible\Repositories\SitesRepository;
use RoundlyConsulting\Plausible\Repositories\StatsRepository;
use RoundlyConsulting\Plausible\Testing\PlausibleFake;

class Plausible
{
    public function stats(): StatsRepository
    {
        return resolve(StatsRepository::class);
    }

    public function events(): EventsRepository
    {
        return resolve(EventsRepository::class);
    }

    public function sites(): SitesRepository
    {
        return resolve(SitesRepository::class);
    }

    /**
     * Swap the bound manager for a recording {@see PlausibleFake} and return it,
     * so host-application tests can assert on Plausible activity.
     */
    public static function fake(): PlausibleFake
    {
        $fake = new PlausibleFake;

        app()->instance(self::class, $fake);
        Facade::clearResolvedInstance(self::class);

        return $fake;
    }
}
