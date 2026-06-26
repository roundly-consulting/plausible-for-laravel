<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Exceptions;

final class UniqueVisitorIdentifierRequired extends PlausibleException
{
    public static function error(): self
    {
        return new self(
            'Unique visitor identifier required to properly capture visitors. '.
            'Use `setVisitor` method before `capture`. '.
            'For more info see: https://plausible.io/docs/events-api#unique-visitor-tracking'
        );
    }
}
