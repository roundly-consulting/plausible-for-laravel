<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;

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
