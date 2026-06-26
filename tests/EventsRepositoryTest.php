<?php

declare(strict_types=1);

use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use RoundlyConsulting\Plausible\Exceptions\MissingConfiguration;
use RoundlyConsulting\Plausible\Exceptions\UniqueVisitorIdentifierRequired;
use RoundlyConsulting\Plausible\Jobs\CaptureEventJob;
use RoundlyConsulting\Plausible\Requests\Events\CaptureEventRequest;

function fakeRequest(): Request
{
    return Request::create('/checkout', 'POST', server: [
        'HTTP_USER_AGENT' => 'Custom Agent/1.0',
        'REMOTE_ADDR' => '5.178.50.178',
    ]);
}

it('lifts the visitor from the current request', function () {
    useHttpSnapshot('events/capture');

    plausible()->events()
        ->fromRequest(fakeRequest())
        ->capture(CaptureEventRequest::make(site: 'localhost', url: 'https://localhost/x'));

    Http::assertSent(
        fn ($request) => $request->hasHeader('User-Agent', 'Custom Agent/1.0')
            && $request->hasHeader('X-Forwarded-For', '5.178.50.178'),
    );
});

it('captures a pageview using an explicit site', function () {
    useHttpSnapshot('events/capture');

    $response = plausible()->events()
        ->fromRequest(fakeRequest())
        ->pageview('https://localhost/blog', 'localhost');

    expect($response)->toBeInstanceOf(Response::class);

    Http::assertSent(
        fn ($request) => $request['name'] === 'pageview'
            && $request['url'] === 'https://localhost/blog'
            && $request['domain'] === 'localhost',
    );
});

it('captures a pageview using the default site', function () {
    config()->set('plausible.site', 'configured.test');
    useHttpSnapshot('events/capture');

    plausible()->events()->fromRequest(fakeRequest())->pageview('https://configured.test/x');

    Http::assertSent(fn ($request) => $request['domain'] === 'configured.test');
});

it('throws when a pageview has no site', function () {
    config()->set('plausible.site', null);

    plausible()->events()->fromRequest(fakeRequest())->pageview('https://x.test/y');
})->throws(MissingConfiguration::class);

it('queues a capture as a job', function () {
    Queue::fake();

    plausible()->events()
        ->fromRequest(fakeRequest())
        ->captureQueued(CaptureEventRequest::make(site: 'localhost', url: 'https://localhost/x', name: 'purchase'));

    Queue::assertPushed(
        CaptureEventJob::class,
        fn (CaptureEventJob $job) => $job->request->name === 'purchase'
            && $job->userAgent === 'Custom Agent/1.0'
            && $job->ipAddress === '5.178.50.178',
    );
});

it('throws when queueing without a visitor', function () {
    plausible()->events()
        ->captureQueued(CaptureEventRequest::make(site: 'localhost', url: 'https://localhost/x'));
})->throws(UniqueVisitorIdentifierRequired::class);

it('performs the http call when the job is handled', function () {
    useHttpSnapshot('events/capture');

    $job = new CaptureEventJob(
        CaptureEventRequest::make(site: 'localhost', url: 'https://localhost/x', name: 'purchase'),
        'Custom Agent/1.0',
        '5.178.50.178',
    );

    $job->handle();

    Http::assertSent(
        fn ($request) => str_ends_with($request->url(), '/api/event')
            && $request['name'] === 'purchase',
    );
});
