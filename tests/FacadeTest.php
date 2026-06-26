<?php

declare(strict_types=1);

use RoundlyConsulting\Plausible\Facades\Plausible;
use RoundlyConsulting\Plausible\Repositories\EventsRepository;
use RoundlyConsulting\Plausible\Repositories\SitesRepository;
use RoundlyConsulting\Plausible\Repositories\StatsRepository;

it('resolves repositories through the facade', function () {
    expect(Plausible::stats())->toBeInstanceOf(StatsRepository::class)
        ->and(Plausible::events())->toBeInstanceOf(EventsRepository::class)
        ->and(Plausible::sites())->toBeInstanceOf(SitesRepository::class);
});
