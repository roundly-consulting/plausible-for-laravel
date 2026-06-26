<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Results;

use Illuminate\Http\Client\Response;

/**
 * Typed view over a shared-link payload from the Sites Provisioning API. The
 * `url` is a shareable dashboard link for the site.
 */
final readonly class SharedLinkResult
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public ?string $name,
        public ?string $url,
        public array $data,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $name = $data['name'] ?? null;
        $url = $data['url'] ?? null;

        return new self(
            is_string($name) ? $name : null,
            is_string($url) ? $url : null,
            $data,
        );
    }

    public static function fromResponse(Response $response): self
    {
        $data = $response->json();

        return self::fromArray(is_array($data) ? $data : []);
    }
}
