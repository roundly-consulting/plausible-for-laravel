<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Requests\Stats;

use RoundlyConsulting\Plausible\Enums\Dimension;
use RoundlyConsulting\Plausible\Enums\Metric;
use RoundlyConsulting\Plausible\Enums\Period;
use RoundlyConsulting\Plausible\Support\Value;

/**
 * Models the JSON body of the Stats v2 `/api/v2/query` endpoint. The
 * `date_range` is either a relative string/{@see Period} (e.g. `30d`) or a
 * `[start, end]` array of ISO dates. `filters`/`order_by`/`include`/`pagination`
 * are the API's own transport shapes for the POST body.
 */
final class QueryRequest
{
    /**
     * @param  list<string|Metric>  $metrics
     * @param  string|Period|list<string>  $dateRange
     * @param  list<string|Dimension>  $dimensions
     * @param  list<array<int, mixed>>  $filters
     * @param  list<array<int, string>>  $orderBy
     * @param  array<string, mixed>  $include
     */
    public function __construct(
        public readonly string $site,
        public readonly array $metrics,
        public readonly string|Period|array $dateRange = '30d',
        public readonly array $dimensions = [],
        public readonly array $filters = [],
        public readonly array $orderBy = [],
        public readonly array $include = [],
        public readonly ?int $limit = null,
        public readonly ?int $offset = null,
    ) {}

    /**
     * @param  list<string|Metric>  $metrics
     * @param  string|Period|list<string>  $dateRange
     * @param  list<string|Dimension>  $dimensions
     * @param  list<array<int, mixed>>  $filters
     * @param  list<array<int, string>>  $orderBy
     * @param  array<string, mixed>  $include
     */
    public static function make(
        string $site,
        array $metrics,
        string|Period|array $dateRange = '30d',
        array $dimensions = [],
        array $filters = [],
        array $orderBy = [],
        array $include = [],
        ?int $limit = null,
        ?int $offset = null,
    ): self {
        return new self($site, $metrics, $dateRange, $dimensions, $filters, $orderBy, $include, $limit, $offset);
    }

    /**
     * @return array<string, mixed>
     */
    public function toBody(): array
    {
        $body = [
            'site_id' => $this->site,
            'metrics' => Value::list($this->metrics),
            'date_range' => is_array($this->dateRange) ? $this->dateRange : Value::of($this->dateRange),
        ];

        if ($this->dimensions !== []) {
            $body['dimensions'] = Value::list($this->dimensions);
        }

        if ($this->filters !== []) {
            $body['filters'] = $this->filters;
        }

        if ($this->orderBy !== []) {
            $body['order_by'] = $this->orderBy;
        }

        if ($this->include !== []) {
            $body['include'] = $this->include;
        }

        if ($this->limit !== null || $this->offset !== null) {
            $body['pagination'] = array_filter([
                'limit' => $this->limit,
                'offset' => $this->offset,
            ], static fn (?int $value): bool => $value !== null);
        }

        return $body;
    }
}
