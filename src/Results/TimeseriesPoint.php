<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Results;

use Illuminate\Support\Carbon;
use RoundlyConsulting\Plausible\Enums\Metric;
use RoundlyConsulting\Plausible\Support\Value;

/**
 * A single bucket of a timeseries: its date and the metrics measured for it.
 */
final readonly class TimeseriesPoint
{
    /**
     * @param  array<string, float|int>  $metrics
     */
    public function __construct(
        public Carbon $date,
        public array $metrics,
    ) {}

    public function metric(string|Metric $metric): float|int
    {
        return $this->metrics[Value::of($metric)] ?? 0;
    }
}
