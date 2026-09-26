<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\DataTransferObjects;

use RoundlyConsulting\Money\Exceptions\InvalidCurrency;
use RoundlyConsulting\Money\Exceptions\MoneyException;
use RoundlyConsulting\Money\Exceptions\UnknownCurrency;
use RoundlyConsulting\Money\Money;
use RoundlyConsulting\Plausible\Exceptions\InvalidRevenue;

/**
 * Revenue attached to a captured event, as an exact Money. The payload carries the amount
 * as a decimal string, so no float ever sits between the host and the HTTP body.
 */
final readonly class Revenue
{
    public function __construct(public Money $amount)
    {
        // Plausible converts ISO 4217 currencies only; a custom one would be dropped silently.
        if (! $amount->currency()->iso) {
            throw InvalidRevenue::currency($amount->currency()->code);
        }
    }

    public static function make(Money $amount): self
    {
        return new self($amount);
    }

    /**
     * @param  string|int  $amount  decimal major units, e.g. "12.99" — never a float
     *
     * @throws InvalidRevenue
     */
    public static function of(string|int $amount, string $currency): self
    {
        try {
            $money = Money::ofMajor($amount, $currency);
        } catch (UnknownCurrency|InvalidCurrency $e) {
            throw InvalidRevenue::currency($currency, $e);
        } catch (MoneyException $e) {
            throw InvalidRevenue::amount((string) $amount, $e->getMessage(), $e);
        }

        return new self($money);
    }

    /**
     * @return array{currency: string, amount: string}
     */
    public function toArray(): array
    {
        return [
            'currency' => $this->amount->currency()->code,
            'amount' => $this->amount->toDecimal(),
        ];
    }
}
