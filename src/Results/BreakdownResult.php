<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Results;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;

/**
 * Typed view over an `/api/v1/stats/breakdown` response.
 */
final readonly class BreakdownResult
{
    /**
     * @param  list<BreakdownRow>  $rows
     */
    public function __construct(
        public array $rows,
        private Response $response,
    ) {}

    public static function fromResponse(Response $response): self
    {
        $results = $response->json('results');
        $rows = [];

        if (is_array($results)) {
            foreach ($results as $row) {
                if (is_array($row)) {
                    /** @var array<string, float|int|string> $row */
                    $rows[] = new BreakdownRow($row);
                }
            }
        }

        return new self($rows, $response);
    }

    /**
     * @return list<BreakdownRow>
     */
    public function rows(): array
    {
        return $this->rows;
    }

    /**
     * @return Collection<int, BreakdownRow>
     */
    public function collect(): Collection
    {
        return new Collection($this->rows);
    }

    public function response(): Response
    {
        return $this->response;
    }
}
