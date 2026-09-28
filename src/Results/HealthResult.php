<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Results;

use RoundlyConsulting\Plausible\PlausibleManager;

/**
 * Outcome of a configuration + connectivity health check (see
 * {@see PlausibleManager::health()}).
 *
 * - `reachable` — the instance answered: no connection failure, no 5xx, no 404.
 * - `authorized` — the probe was answered 2xx with the configured API key.
 */
final readonly class HealthResult
{
    public function __construct(
        public bool $hasUrl,
        public bool $hasKey,
        public bool $reachable,
        public bool $authorized,
        public ?int $status,
        public string $message,
    ) {}

    public function isConfigured(): bool
    {
        return $this->hasUrl && $this->hasKey;
    }

    /**
     * Configured, reachable, and the API key was accepted.
     */
    public function ok(): bool
    {
        return $this->isConfigured() && $this->reachable && $this->authorized;
    }
}
