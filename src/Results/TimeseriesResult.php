<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Results;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Typed view over an `/api/v1/stats/timeseries` response.
 */
final readonly class TimeseriesResult
{
    /**
     * @param  list<TimeseriesPoint>  $points
     */
    public function __construct(
        public array $points,
        private Response $response,
    ) {}

    public static function fromResponse(Response $response): self
    {
        $rows = $response->json('results');
        $points = [];

        if (is_array($rows)) {
            foreach ($rows as $row) {
                if (! is_array($row)) {
                    continue;
                }

                $date = $row['date'] ?? null;

                /** @var array<string, float|int> $metrics */
                $metrics = array_filter(
                    $row,
                    static fn (string $key): bool => $key !== 'date',
                    ARRAY_FILTER_USE_KEY,
                );

                $points[] = new TimeseriesPoint(
                    Carbon::parse(is_string($date) ? $date : 'now'),
                    $metrics,
                );
            }
        }

        return new self($points, $response);
    }

    /**
     * @return list<TimeseriesPoint>
     */
    public function points(): array
    {
        return $this->points;
    }

    /**
     * @return Collection<int, TimeseriesPoint>
     */
    public function collect(): Collection
    {
        return new Collection($this->points);
    }

    public function response(): Response
    {
        return $this->response;
    }
}
