<?php

declare(strict_types=1);

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RoundlyConsulting\HttpClientRateLimits\Facades\RateLimits;

it('attaches the bearer token and base url to every request', function () {
    useHttpSnapshot('stats/realtime');

    plausible()->stats()->realtime('localhost');

    Http::assertSent(
        fn ($request) => $request->hasHeader('Authorization', 'Bearer test-api-key')
            && str_starts_with($request->url(), 'http://localhost:8000'),
    );
});

it('retries transient failures when configured', function () {
    config()->set('plausible.retry.times', 2);
    config()->set('plausible.retry.sleep_ms', 0);

    Http::fakeSequence()
        ->push('', 500)
        ->push('7', 200);

    $visitors = plausible()->stats()->realtime('localhost');

    expect($visitors)->toBe(7);

    Http::assertSentCount(2);
});

it('exposes the configured default site', function () {
    config()->set('plausible.site', 'default-site.com');

    expect(plausible()->stats()->defaultSite())->toBe('default-site.com');

    config()->set('plausible.site', null);

    expect(plausible()->stats()->defaultSite())->toBeNull();
});

// Retries are for transient failures only. A 4xx is final, and a 429 must reach the rate
// limiter as a response so its Retry-After throttles the next call.
it('does not retry a 4xx response', function (int $status) {
    RateLimits::fake();
    config()->set('plausible.retry.times', 3);
    config()->set('plausible.retry.sleep_ms', 0);
    $attempts = 0;
    answerAttempts([$status], $attempts);

    expect(fn () => plausible()->stats()->realtime('localhost'))->toThrow(RequestException::class)
        ->and($attempts)->toBe(1);
})->with([400, 401, 429]);

it('retries a server error and a failed connection', function (int|string $failure) {
    RateLimits::fake();
    config()->set('plausible.retry.times', 3);
    config()->set('plausible.retry.sleep_ms', 0);
    $attempts = 0;
    answerAttempts([$failure, 200], $attempts);

    expect(plausible()->stats()->realtime('localhost'))->toBe(7)
        ->and($attempts)->toBe(2);
})->with([
    'a 503' => [503],
    'a timeout' => ['cURL error 28: Operation timed out after 10001 milliseconds with 0 bytes received'],
]);

it('lets the rate limiter read a 429 Retry-After with retries on', function () {
    $fake = RateLimits::fake();
    config()->set('plausible.retry.times', 3);
    config()->set('plausible.retry.sleep_ms', 0);
    $attempts = 0;
    answerAttempts([429], $attempts, headers: ['Retry-After' => '2']);

    expect(fn () => plausible()->stats()->realtime('localhost'))->toThrow(RequestException::class);

    try {
        plausible()->stats()->realtime('localhost');
    } catch (RequestException) {
        // still the faked 429 — only the deferral matters here
    }

    $fake->assertDeferred('plausible:stats:app:localhost');
});

it('still throws the server error once the retries run out', function () {
    RateLimits::fake();
    config()->set('plausible.retry.times', 2);
    config()->set('plausible.retry.sleep_ms', 0);
    $attempts = 0;
    answerAttempts([503], $attempts);

    expect(fn () => plausible()->stats()->realtime('localhost'))->toThrow(RequestException::class)
        ->and($attempts)->toBe(2);
});
