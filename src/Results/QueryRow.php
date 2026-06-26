<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Results;

/**
 * A single row of a Stats v2 `/api/v2/query` response: the ordered metric values
 * aligned with the query's `metrics`, and the dimension values aligned with its
 * `dimensions`.
 */
final readonly class QueryRow
{
    /**
     * @param  list<float|int>  $metrics
     * @param  list<string>  $dimensions
     */
    public function __construct(
        public array $metrics,
        public array $dimensions,
    ) {}

    public function metric(int $index = 0): float|int
    {
        return $this->metrics[$index] ?? 0;
    }

    public function dimension(int $index = 0): ?string
    {
        return $this->dimensions[$index] ?? null;
    }
}
