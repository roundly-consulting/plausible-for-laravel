<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Tests;

use Illuminate\Support\ServiceProvider;
use RoundlyConsulting\HttpClientRateLimits\HttpClientRateLimitsServiceProvider;
use RoundlyConsulting\Money\MoneyServiceProvider;
use RoundlyConsulting\Plausible\PlausibleServiceProvider;
use RoundlyConsulting\Testing\PackageTestCase;

abstract class TestCase extends PackageTestCase
{
    /**
     * Every provider plausible hard-requires, in registration order. A host auto-discovers
     * http-client-rate-limits and money; the suite must list them or the test environment is
     * a fiction — every outbound Stats/Events/Sites request is paced through the limiter, and
     * every Revenue resolves its currency through money's registry.
     *
     * @return list<class-string<ServiceProvider>>
     */
    protected function packageProviders(): array
    {
        return [
            HttpClientRateLimitsServiceProvider::class,
            MoneyServiceProvider::class,
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
            // A registered non-ISO currency — Revenue must refuse it (Plausible converts ISO only).
            'money.currencies.custom' => ['PTS' => ['exponent' => 0, 'name' => 'Loyalty points']],
        ];
    }
}
