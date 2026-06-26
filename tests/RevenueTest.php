<?php

declare(strict_types=1);

use RoundlyConsulting\Plausible\DataTransferObjects\Revenue;
use RoundlyConsulting\Plausible\Exceptions\InvalidRevenue;
use RoundlyConsulting\Plausible\Requests\Events\CaptureEventRequest;

it('serialises a valid revenue and upper-cases the currency', function () {
    $revenue = Revenue::make(currency: 'eur', amount: 49.0);

    expect($revenue->toArray())->toBe(['currency' => 'EUR', 'amount' => 49.0]);
});

it('accepts a string amount for precision', function () {
    $revenue = new Revenue(currency: 'USD', amount: '12.99');

    expect($revenue->toArray())->toBe(['currency' => 'USD', 'amount' => '12.99']);
});

it('rejects an invalid currency code', function () {
    new Revenue(currency: 'EURO', amount: 1.0);
})->throws(InvalidRevenue::class);

it('includes revenue and interactive in the event payload only when set', function () {
    $withRevenue = CaptureEventRequest::make(
        site: 'localhost',
        url: 'https://localhost/checkout',
        name: 'purchase',
        revenue: new Revenue('EUR', 49.0),
        interactive: false,
    )->toQuery()->toArray();

    expect($withRevenue)->toHaveKey('revenue', ['currency' => 'EUR', 'amount' => 49.0])
        ->and($withRevenue)->toHaveKey('interactive', false);

    $plain = CaptureEventRequest::make(
        site: 'localhost',
        url: 'https://localhost/blog',
    )->toQuery()->toArray();

    expect($plain)->not->toHaveKey('revenue')
        ->and($plain)->not->toHaveKey('interactive');
});
