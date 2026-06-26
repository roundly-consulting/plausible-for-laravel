<?php

declare(strict_types=1);

use RoundlyConsulting\Plausible\Plausible;

it('returns plausible instance', function () {
    $plausible = plausible();

    expect($plausible)->toBeInstanceOf(Plausible::class);
});
