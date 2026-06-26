<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Results;

use Illuminate\Http\Client\Response;

/**
 * Typed view over a Sites API site payload (`domain`, `timezone`, …).
 */
final readonly class SiteResult
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public ?string $domain,
        public ?string $timezone,
        public array $data,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $domain = $data['domain'] ?? null;
        $timezone = $data['timezone'] ?? null;

        return new self(
            is_string($domain) ? $domain : null,
            is_string($timezone) ? $timezone : null,
            $data,
        );
    }

    public static function fromResponse(Response $response): self
    {
        $data = $response->json();

        return self::fromArray(is_array($data) ? $data : []);
    }
}
