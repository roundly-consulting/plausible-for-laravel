<?php

declare(strict_types=1);

use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Support\Facades\Http;
use RoundlyConsulting\Plausible\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

function snapshot(string $name): PromiseInterface
{
    $json = (string) file_get_contents(__DIR__.'/responses/'.$name.'.json');

    return Http::response(
        body: $json,
        headers: ['Content-Type' => 'application/json'],
    );
}

function useHttpSnapshot(string $name): void
{
    Http::fake([
        '*' => snapshot($name),
    ]);
}
