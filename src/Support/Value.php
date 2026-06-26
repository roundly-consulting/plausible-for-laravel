<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Support;

use BackedEnum;

/**
 * Normalises the `string|BackedEnum` values the public API accepts (metrics,
 * periods, intervals, properties, dimensions, …) down to the plain strings the
 * Plausible API expects. This is what keeps the enums and raw strings
 * interchangeable everywhere.
 */
final class Value
{
    public static function of(string|BackedEnum $value): string
    {
        return $value instanceof BackedEnum ? (string) $value->value : $value;
    }

    /**
     * @param  list<string|BackedEnum>  $values
     * @return list<string>
     */
    public static function list(array $values): array
    {
        return array_map(self::of(...), $values);
    }
}
