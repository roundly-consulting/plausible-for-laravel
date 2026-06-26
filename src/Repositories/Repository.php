<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Repositories;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

abstract class Repository
{
    protected function request(): PendingRequest
    {
        return Http::baseUrl((string) config('plausible.url'))
            ->withToken((string) config('plausible.key'))
            ->throw();
    }
}
