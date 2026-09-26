<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Exceptions;

use Throwable;

final class InvalidRevenue extends PlausibleException
{
    public static function currency(string $currency, ?Throwable $previous = null): self
    {
        return new self(
            "Invalid revenue currency [{$currency}]. Use a 3-letter ISO 4217 code, e.g. EUR.",
            previous: $previous,
        );
    }

    public static function amount(string $input, string $reason, ?Throwable $previous = null): self
    {
        return new self("Invalid revenue amount [{$input}]: {$reason}", previous: $previous);
    }
}
