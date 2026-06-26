<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Results;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;

/**
 * Typed view over a Stats v2 `/api/v2/query` response (`results`, `meta`, `query`).
 */
final readonly class QueryResult
{
    /**
     * @param  list<QueryRow>  $rows
     * @param  array<string, mixed>  $meta
     * @param  array<string, mixed>  $query
     */
    public function __construct(
        public array $rows,
        public array $meta,
        public array $query,
        private Response $response,
    ) {}

    public static function fromResponse(Response $response): self
    {
        $results = $response->json('results');
        $rows = [];

        if (is_array($results)) {
            foreach ($results as $row) {
                if (! is_array($row)) {
                    continue;
                }

                /** @var list<float|int> $metrics */
                $metrics = array_values(is_array($row['metrics'] ?? null) ? $row['metrics'] : []);
                /** @var list<string> $dimensions */
                $dimensions = array_values(is_array($row['dimensions'] ?? null) ? $row['dimensions'] : []);

                $rows[] = new QueryRow($metrics, $dimensions);
            }
        }

        $meta = $response->json('meta');
        $query = $response->json('query');

        return new self(
            $rows,
            is_array($meta) ? $meta : [],
            is_array($query) ? $query : [],
            $response,
        );
    }

    /**
     * @return list<QueryRow>
     */
    public function rows(): array
    {
        return $this->rows;
    }

    /**
     * @return Collection<int, QueryRow>
     */
    public function collect(): Collection
    {
        return new Collection($this->rows);
    }

    /**
     * @return array<string, mixed>
     */
    public function meta(): array
    {
        return $this->meta;
    }

    /**
     * @return array<string, mixed>
     */
    public function query(): array
    {
        return $this->query;
    }

    public function response(): Response
    {
        return $this->response;
    }
}
