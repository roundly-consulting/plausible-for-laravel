<?php

declare(strict_types=1);

use RoundlyConsulting\Plausible\PlausibleManager;

if (! function_exists('plausible')) {
    function plausible(): PlausibleManager
    {
        return resolve(PlausibleManager::class);
    }
}
