<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible;

use RoundlyConsulting\Plausible\Repositories\EventsRepository;
use RoundlyConsulting\Plausible\Repositories\SitesRepository;
use RoundlyConsulting\Plausible\Repositories\StatsRepository;

final class Plausible
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
}
