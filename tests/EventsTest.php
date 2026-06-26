<?php

declare(strict_types=1);

use Illuminate\Http\Client\Response;
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
