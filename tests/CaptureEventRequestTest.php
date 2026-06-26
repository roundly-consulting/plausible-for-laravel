<?php

declare(strict_types=1);

use RoundlyConsulting\Plausible\Requests\Events\CaptureEventRequest;
use RoundlyConsulting\Plausible\Requests\Query;

it('creates instance using make static method', function () {
    $request = CaptureEventRequest::make(
        site: 'testing.site',
        url: 'https://testing.site/blog',
    );

    expect($request)
        ->toBeInstanceOf(CaptureEventRequest::class)
        ->site->toBe('testing.site')
        ->url->toBe('https://testing.site/blog')
        ->name->toBe('pageview');
});

it('detects referrer and custom props', function () {
    $without = new CaptureEventRequest(
        site: 'testing.site',
        url: 'https://testing.site/blog',
    );

    expect($without->hasReferrer())->toBeFalse()
        ->and($without->hasCustomProps())->toBeFalse();

    $with = new CaptureEventRequest(
        site: 'testing.site',
        url: 'https://testing.site/blog',
        referrer: 'https://affiliate.test',
        props: ['plan' => 'pro'],
    );

    expect($with->hasReferrer())->toBeTrue()
        ->and($with->hasCustomProps())->toBeTrue();
});

it('builds the query payload', function () {
    $request = new CaptureEventRequest(
        site: 'testing.site',
        url: 'https://testing.site/blog',
        name: 'signup',
        referrer: 'https://affiliate.test',
        props: ['plan' => 'pro'],
    );

    $query = $request->toQuery();

    expect($query)
        ->toBeInstanceOf(Query::class)
        ->toArray()
        ->toBe([
            'domain' => 'testing.site',
            'name' => 'signup',
            'url' => 'https://testing.site/blog',
            'referrer' => 'https://affiliate.test',
            'props' => ['plan' => 'pro'],
        ]);
});
