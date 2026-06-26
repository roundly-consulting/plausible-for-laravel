<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Requests;

use Closure;

final class Query
{
    /**
     * @param  array<string, mixed>  $query
     */
    public function __construct(
        private array $query = [],
    ) {}

    /**
     * @param  array<string, mixed>  $query
     */
    public static function make(array $query = []): self
    {
        return new self($query);
    }

    public function when(bool $bool, string $key, mixed $value): self
    {
        if ($bool) {
            $this->set($key, $value instanceof Closure ? $value() : $value);
        }

        return $this;
    }

    public function set(string $key, mixed $value): self
    {
        $this->query[$key] = $value;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->query;
    }
}
