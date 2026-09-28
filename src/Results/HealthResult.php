<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Results;

use RoundlyConsulting\Plausible\PlausibleManager;

/**
 * Outcome of a configuration + connectivity health check (see
 * {@see PlausibleManager::health()}).
 */
final readonly class HealthResult
{
    public function __construct(
        public bool $hasUrl,
        public bool $hasKey,
        public bool $reachable,
        public ?int $status,
        public string $message,
    ) {}

    public function isConfigured(): bool
    {
        return $this->hasUrl && $this->hasKey;
    }

    public function ok(): bool
    {
        return $this->isConfigured() && $this->reachable;
    }
}
