<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\DataTransferObjects;

use RoundlyConsulting\Plausible\Exceptions\InvalidRevenue;

/**
 * Revenue attached to a captured event. `amount` accepts a float or a string so
 * money can be passed without precision loss (Plausible accepts either).
 */
final readonly class Revenue
{
    public function __construct(
        public string $currency,
        public float|string $amount,
    ) {
        if (preg_match('/^[A-Za-z]{3}$/', $currency) !== 1) {
            throw InvalidRevenue::currency($currency);
        }
    }

    public static function make(string $currency, float|string $amount): self
    {
        return new self($currency, $amount);
    }

    /**
     * @return array{currency: string, amount: float|string}
     */
    public function toArray(): array
    {
        return [
            'currency' => strtoupper($this->currency),
            'amount' => $this->amount,
        ];
    }
}
