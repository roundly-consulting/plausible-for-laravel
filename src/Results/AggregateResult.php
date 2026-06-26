<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Results;

use Illuminate\Http\Client\Response;
use RoundlyConsulting\Plausible\Enums\Metric;
use RoundlyConsulting\Plausible\Support\Value;

/**
 * Typed view over an `/api/v1/stats/aggregate` response. Each metric exposes its
 * `value` and, when a comparison was requested, its `change`.
 */
final readonly class AggregateResult
{
    /**
     * @param  array<string, array<string, float|int>>  $results
     */
    public function __construct(
        public array $results,
        private Response $response,
    ) {}

    public static function fromResponse(Response $response): self
    {
        $results = $response->json('results');

        return new self(is_array($results) ? $results : [], $response);
    }

    public function metric(string|Metric $metric): float|int
    {
        return $this->results[Value::of($metric)]['value'] ?? 0;
    }

    public function change(string|Metric $metric): float|int|null
    {
        return $this->results[Value::of($metric)]['change'] ?? null;
    }

    public function has(string|Metric $metric): bool
    {
        return array_key_exists(Value::of($metric), $this->results);
    }

    public function response(): Response
    {
        return $this->response;
    }
}
