<?php

declare(strict_types=1);

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use RoundlyConsulting\HttpClientRateLimits\Facades\RateLimits;
use RoundlyConsulting\Plausible\Exceptions\UniqueVisitorIdentifierRequired;
use RoundlyConsulting\Plausible\Requests\Events\CaptureEventRequest;

it('throws exception when no visitor identifiers are set', function () {
    plausible()
        ->events()
        ->capture(CaptureEventRequest::make(
            site: 'localhost',
            url: 'http://plausible-test.test/test',
        ));
})->expectException(UniqueVisitorIdentifierRequired::class);

it('captures event', function () {
    useHttpSnapshot('events/capture');

    $metrics = plausible()
        ->events()
        ->setVisitor('Custom Agent/1.0', '5.178.50.178')
        ->capture(CaptureEventRequest::make(
            site: 'localhost',
            url: 'http://plausible-test.test/test',
        ));

    expect($metrics)->toBeInstanceOf(Response::class);
});

// POST /api/event is not idempotent: after a timeout Plausible may already have recorded the
// event, so a retry could count it (and its revenue) twice.
it('never retries an event capture that timed out', function () {
    RateLimits::fake();
    config()->set('plausible.retry.times', 3);
    config()->set('plausible.retry.sleep_ms', 0);
    $attempts = 0;
    answerAttempts(['cURL error 28: Operation timed out after 10001 milliseconds with 0 bytes received', 202], $attempts);

    expect(fn () => plausible()->events()->setVisitor('Agent/1.0', '5.5.5.5')
        ->capture(CaptureEventRequest::make(site: 'localhost', url: 'https://x.test/')))
        ->toThrow(ConnectionException::class)
        ->and($attempts)->toBe(1);
});

it('retries an event capture that failed before reaching Plausible', function (int|string $failure) {
    RateLimits::fake();
    config()->set('plausible.retry.times', 3);
    config()->set('plausible.retry.sleep_ms', 0);
    $attempts = 0;
    answerAttempts([$failure, 202], $attempts, body: 'ok');

    plausible()->events()->setVisitor('Agent/1.0', '5.5.5.5')
        ->capture(CaptureEventRequest::make(site: 'localhost', url: 'https://x.test/'));

    expect($attempts)->toBe(2);
})->with([
    'a refused connection' => ['cURL error 7: Failed to connect to localhost port 8000: Connection refused'],
    'a 503' => [503],
]);
