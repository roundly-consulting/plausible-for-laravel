<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Results;

use Illuminate\Http\Client\Response;

/**
 * Typed view over a goal payload from the Sites Provisioning API.
 */
final readonly class GoalResult
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public int|string|null $id,
        public ?string $goalType,
        public ?string $eventName,
        public ?string $pagePath,
        public array $data,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $id = $data['id'] ?? null;
        $goalType = $data['goal_type'] ?? null;
        $eventName = $data['event_name'] ?? null;
        $pagePath = $data['page_path'] ?? null;

        return new self(
            is_int($id) || is_string($id) ? $id : null,
            is_string($goalType) ? $goalType : null,
            is_string($eventName) ? $eventName : null,
            is_string($pagePath) ? $pagePath : null,
            $data,
        );
    }

    public static function fromResponse(Response $response): self
    {
        $data = $response->json();

        return self::fromArray(is_array($data) ? $data : []);
    }
}
