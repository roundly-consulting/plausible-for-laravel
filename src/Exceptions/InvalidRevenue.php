<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Exceptions;

final class InvalidRevenue extends PlausibleException
{
    public static function currency(string $currency): self
    {
        return new self(
            "Invalid revenue currency [{$currency}]. Use a 3-letter ISO 4217 code, e.g. EUR."
        );
    }
}
