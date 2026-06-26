<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Support;

use GuzzleHttp\Psr7\Response as Psr7Response;
use Illuminate\Http\Client\Response;

/**
 * Rebuilds an {@see Response} from a decoded JSON payload. Used by the stats
 * query cache, which stores the plain response body (a serialisable array)
 * rather than the live {@see Response} (whose stream resource cannot be cached).
 */
final class ResponseFactory
{
    /**
     * @param  array<mixed>  $body
     */
    public static function json(array $body, int $status = 200): Response
    {
        return new Response(
            new Psr7Response($status, ['Content-Type' => 'application/json'], (string) json_encode($body)),
        );
    }
}
