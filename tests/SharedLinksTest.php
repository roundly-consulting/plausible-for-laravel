<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use RoundlyConsulting\Plausible\Repositories\SharedLinksRepository;
use RoundlyConsulting\Plausible\Requests\Sites\CreateSharedLinkRequest;
use RoundlyConsulting\Plausible\Results\SharedLinkResult;

it('creates a shared link and returns the shareable url', function () {
    useHttpSnapshot('sites/shared-link');

    $link = plausible()->sharedLinks()->create('localhost', 'Public dashboard');

    expect($link)->toBeInstanceOf(SharedLinkResult::class)
        ->and($link->name)->toBe('Public dashboard')
        ->and($link->url)->toContain('/share/');

    Http::assertSent(function ($request) {
        $body = $request->data();

        return $request->method() === 'PUT'
            && str_contains($request->url(), '/api/v1/sites/shared-links')
            && $body['site_id'] === 'localhost'
            && $body['name'] === 'Public dashboard';
    });
});

it('sends only the documented site_id and name', function () {
    useHttpSnapshot('sites/shared-link');

    plausible()->sharedLinks()->create('localhost', 'Open');

    Http::assertSent(fn ($request) => $request->data() === ['site_id' => 'localhost', 'name' => 'Open']);
});

it('offers no password option Plausible would silently ignore', function () {
    // Plausible's shared-links endpoint documents only site_id and name: a password sent
    // there leaves the link public while the caller believes it is protected.
    expect((new ReflectionMethod(SharedLinksRepository::class, 'create'))->getNumberOfParameters())->toBe(2)
        ->and((new ReflectionMethod(CreateSharedLinkRequest::class, 'make'))->getNumberOfParameters())->toBe(2)
        ->and(property_exists(CreateSharedLinkRequest::class, 'password'))->toBeFalse()
        ->and(CreateSharedLinkRequest::make('localhost', 'Open')->toQuery()->toArray())
        ->toBe(['site_id' => 'localhost', 'name' => 'Open']);
});

it('parses shared-link results from arbitrary arrays', function () {
    $link = SharedLinkResult::fromArray([]);

    expect($link->name)->toBeNull()->and($link->url)->toBeNull();
});
