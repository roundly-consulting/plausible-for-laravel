<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use RoundlyConsulting\Plausible\Requests\Sites\UpdateSiteRequest;
use RoundlyConsulting\Plausible\Results\SiteResult;

it('gets a single site', function () {
    useHttpSnapshot('sites/get');

    $site = plausible()->sites()->get('lorem.test');

    expect($site)->toBeInstanceOf(SiteResult::class)
        ->and($site->domain)->toBe('lorem.test')
        ->and($site->timezone)->toBe('Etc/UTC');

    Http::assertSent(
        fn ($request) => $request->method() === 'GET'
            && str_ends_with($request->url(), '/api/v1/sites/lorem.test'),
    );
});

it('lists sites', function () {
    useHttpSnapshot('sites/list');

    $sites = plausible()->sites()->list();

    expect($sites)->toHaveCount(2)
        ->and($sites[0])->toBeInstanceOf(SiteResult::class)
        ->and($sites[0]->domain)->toBe('lorem.test')
        ->and($sites[1]->timezone)->toBe('Europe/Bratislava');

    Http::assertSent(
        fn ($request) => $request->method() === 'GET'
            && str_ends_with($request->url(), '/api/v1/sites'),
    );
});

it('returns an empty list when the payload has no sites', function () {
    Http::fake(['*' => Http::response(['unexpected' => true], 200)]);

    expect(plausible()->sites()->list())->toBe([]);
});

it('updates a site', function () {
    useHttpSnapshot('sites/update');

    $site = plausible()->sites()->update(UpdateSiteRequest::make(
        site: 'lorem.test',
        domain: 'dolor.test',
        timezone: 'Europe/Bratislava',
    ));

    expect($site)->toBeInstanceOf(SiteResult::class)
        ->and($site->domain)->toBe('dolor.test');

    Http::assertSent(
        fn ($request) => $request->method() === 'PUT'
            && str_ends_with($request->url(), '/api/v1/sites/lorem.test')
            && $request['domain'] === 'dolor.test'
            && $request['timezone'] === 'Europe/Bratislava',
    );
});

it('builds the site result from a non-array payload', function () {
    $site = SiteResult::fromArray([]);

    expect($site->domain)->toBeNull()
        ->and($site->timezone)->toBeNull()
        ->and($site->data)->toBe([]);
});
