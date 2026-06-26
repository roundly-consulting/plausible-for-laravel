<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Results;

use RoundlyConsulting\Plausible\Enums\Metric;
use RoundlyConsulting\Plausible\Support\Value;

/**
 * A single row of a breakdown: the dimension value(s) plus the requested
 * metrics, all under their Plausible response keys.
 */
final readonly class BreakdownRow
{
    /**
     * @param  array<string, float|int|string>  $data
     */
    public function __construct(
        public array $data,
    ) {}

    public function get(string $key): float|int|string|null
    {
        return $this->data[$key] ?? null;
    }

    public function metric(string|Metric $metric): float|int
    {
        $value = $this->data[Value::of($metric)] ?? 0;

        return is_string($value) ? 0 : $value;
    }
}
