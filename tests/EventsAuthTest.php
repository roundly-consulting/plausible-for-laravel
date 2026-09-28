<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use RoundlyConsulting\Plausible\Exceptions\MissingConfiguration;
use RoundlyConsulting\Plausible\Requests\Events\CaptureEventRequest;
use RoundlyConsulting\Plausible\Requests\Stats\AggregateRequest;

it('captures an event without an api key configured', function () {
    config()->set('plausible.key', null);
    useHttpSnapshot('events/capture');

    plausible()->events()->setVisitor('Agent/1.0', '203.0.113.9')
        ->pageview('https://localhost/x', 'localhost');

    Http::assertSent(fn ($request) => $request->url() === 'http://localhost:8000/api/event'
        && ! $request->hasHeader('Authorization'));
});

it('never sends the api key to the events endpoint', function () {
    useHttpSnapshot('events/capture');

    plausible()->events()->setVisitor('Agent/1.0', '203.0.113.9')
        ->capture(CaptureEventRequest::make(site: 'localhost', url: 'https://localhost/x'));

    Http::assertSent(fn ($request) => ! $request->hasHeader('Authorization'));
});

it('still requires the api key for the stats api', function () {
    config()->set('plausible.key', null);
    Http::fake();

    expect(fn () => plausible()->stats()->aggregate(AggregateRequest::make(site: 'localhost')))
        ->toThrow(MissingConfiguration::class, 'Missing Plausible API key');

    Http::assertNothingSent();
});

it('still requires the instance url for event capture', function () {
    config()->set('plausible.url', null);
    Http::fake();

    expect(fn () => plausible()->events()->setVisitor('Agent/1.0', '203.0.113.9')->pageview('https://x/', 'x'))
        ->toThrow(MissingConfiguration::class, 'Missing Plausible URL');
});
