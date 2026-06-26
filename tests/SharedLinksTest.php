<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
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

it('includes a password when provided', function () {
    useHttpSnapshot('sites/shared-link');

    plausible()->sharedLinks()->create('localhost', 'Protected', 'secret');

    Http::assertSent(fn ($request) => $request->data()['password'] === 'secret');
});

it('omits the password when not provided', function () {
    $query = CreateSharedLinkRequest::make('localhost', 'Open')->toQuery()->toArray();

    expect($query)->not->toHaveKey('password');
});

it('parses shared-link results from arbitrary arrays', function () {
    $link = SharedLinkResult::fromArray([]);

    expect($link->name)->toBeNull()->and($link->url)->toBeNull();
});
