<?php

declare(strict_types=1);

use RoundlyConsulting\Plausible\Plausible;

if (! function_exists('plausible')) {
    function plausible(): Plausible
    {
        return resolve(Plausible::class);
    }
}
