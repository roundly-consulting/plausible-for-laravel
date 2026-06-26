<?php

declare(strict_types=1);

use RoundlyConsulting\Plausible\Exceptions\MissingConfiguration;
use RoundlyConsulting\Plausible\Exceptions\PlausibleException;
use RoundlyConsulting\Plausible\Exceptions\UniqueVisitorIdentifierRequired;
use RoundlyConsulting\Plausible\Requests\Stats\AggregateRequest;

it('throws when the url is missing', function () {
    config()->set('plausible.url', null);

    plausible()->stats()->aggregate(AggregateRequest::make(site: 'localhost'));
})->throws(MissingConfiguration::class);

it('throws when the key is missing', function () {
    config()->set('plausible.key', null);

    plausible()->stats()->aggregate(AggregateRequest::make(site: 'localhost'));
})->throws(MissingConfiguration::class);

it('lets consumers catch any package error via the base exception', function () {
    config()->set('plausible.url', '');

    try {
        plausible()->stats()->realtime('localhost');
        $this->fail('Expected a PlausibleException.');
    } catch (PlausibleException $e) {
        expect($e)->toBeInstanceOf(MissingConfiguration::class);
    }
});

it('treats the visitor exception as a package exception', function () {
    expect(UniqueVisitorIdentifierRequired::error())->toBeInstanceOf(PlausibleException::class);
});
