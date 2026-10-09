<?php

declare(strict_types=1);

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Request;
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

/**
 * Answer the n-th HTTP attempt with `$steps[n]` (the last step repeats) and count the
 * attempts in `$attempts` — failed connections included. An int step is that status (with
 * `$body` and `$headers`), a string step a failed connection with that cURL message.
 *
 * @param  list<int|string>  $steps
 * @param  array<string, string>  $headers
 */
function answerAttempts(array $steps, int &$attempts, string $body = '7', array $headers = []): void
{
    Http::fake(function (Request $request) use ($steps, &$attempts, $body, $headers): PromiseInterface {
        $step = $steps[min($attempts, count($steps) - 1)];
        $attempts++;

        return is_string($step)
            ? Create::rejectionFor(new ConnectException($step, $request->toPsrRequest()))
            : Http::response($body, $step, $headers);
    });
}
