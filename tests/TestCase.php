<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Tests;

use Illuminate\Support\ServiceProvider;
use RoundlyConsulting\HttpClientRateLimits\HttpClientRateLimitsServiceProvider;
use RoundlyConsulting\Plausible\PlausibleServiceProvider;
use RoundlyConsulting\Testing\PackageTestCase;

abstract class TestCase extends PackageTestCase
{
    /**
     * Every provider plausible hard-requires, in registration order. A host auto-discovers
     * http-client-rate-limits; the suite must list it or the test environment is a fiction —
     * every outbound Stats/Events/Sites request is paced through its limiter.
     *
     * @return list<class-string<ServiceProvider>>
     */
    protected function packageProviders(): array
    {
        return [
            HttpClientRateLimitsServiceProvider::class,
            PlausibleServiceProvider::class,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function configBeforeBoot(): array
    {
        return [
            'plausible.url' => 'http://localhost:8000',
            'plausible.key' => 'test-api-key',
        ];
    }
}
