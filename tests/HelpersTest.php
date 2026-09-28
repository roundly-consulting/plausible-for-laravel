<?php

declare(strict_types=1);

use RoundlyConsulting\Plausible\PlausibleManager;

it('returns plausible instance', function () {
    $plausible = plausible();

    expect($plausible)->toBeInstanceOf(PlausibleManager::class);
});
