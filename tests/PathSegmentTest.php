<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use RoundlyConsulting\Plausible\Requests\Sites\UpdateSiteRequest;

/*
 * A site id or goal id is one path segment. Encoded, a `/`, `?`, `#` or a dot-segment in a
 * (tenant-supplied) value can no longer re-route the request to another endpoint.
 */

beforeEach(function () {
    Http::fake(['*' => Http::response(['domain' => 'x', 'timezone' => 'UTC', 'deleted' => true])]);
});

it('encodes the site in every sites path', function (Closure $call, string $method) {
    $call('example.com/blog?x=1#y');

    Http::assertSent(fn ($request) => $request->method() === $method
        && $request->url() === 'http://localhost:8000/api/v1/sites/example.com%2Fblog%3Fx%3D1%23y');
})->with([
    'get' => [fn (string $site) => plausible()->sites()->get($site), 'GET'],
    'delete' => [fn (string $site) => plausible()->sites()->delete($site), 'DELETE'],
    'update' => [fn (string $site) => plausible()->sites()->update(UpdateSiteRequest::make(site: $site, domain: 'new.test')), 'PUT'],
]);

it('encodes a dot-segment site so it cannot climb the path', function (string $site, string $encoded) {
    plausible()->sites()->get($site);

    Http::assertSent(fn ($request) => $request->url() === 'http://localhost:8000/api/v1/sites/'.$encoded);
})->with([
    ['..', '%2E%2E'],
    ['.', '%2E'],
]);

it('encodes the goal id in the delete path', function () {
    plausible()->goals()->delete('localhost', '7/../../sites');

    Http::assertSent(fn ($request) => $request->method() === 'DELETE'
        && $request->url() === 'http://localhost:8000/api/v1/sites/goals/7%2F..%2F..%2Fsites');
});

it('leaves a plain domain unchanged', function () {
    plausible()->sites()->get('my.domain.tld');

    Http::assertSent(fn ($request) => $request->url() === 'http://localhost:8000/api/v1/sites/my.domain.tld');
});
