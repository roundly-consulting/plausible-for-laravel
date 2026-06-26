<?php

declare(strict_types=1);

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RoundlyConsulting\Plausible\Requests\Sites\CreateSiteRequest;

it('creates site', function () {
    useHttpSnapshot('sites/create');

    $response = plausible()
        ->sites()
        ->create(CreateSiteRequest::make(
            domain: 'lorem.test',
        ));

    expect($response)->toBeInstanceOf(Response::class);

    Http::assertSent(
        fn ($request) => $request->method() === 'POST'
            && str_ends_with($request->url(), '/api/v1/sites')
            && $request['domain'] === 'lorem.test'
    );
});

it('deletes site', function () {
    useHttpSnapshot('sites/delete');

    $response = plausible()
        ->sites()
        ->delete('lorem.test');

    expect($response)->toBeInstanceOf(Response::class);

    Http::assertSent(
        fn ($request) => $request->method() === 'DELETE'
            && str_ends_with($request->url(), '/api/v1/sites/lorem.test')
    );
});
