<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use RoundlyConsulting\Plausible\PlausibleServiceProvider;

abstract class TestCase extends Orchestra
{
    /** @return array<int, class-string> */
    protected function getPackageProviders($app): array
    {
        return [PlausibleServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        config()->set('plausible.url', 'http://localhost:8000');
        config()->set('plausible.key', 'test-api-key');
    }
}
