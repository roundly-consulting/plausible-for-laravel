<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use RoundlyConsulting\Money\Currency;
use RoundlyConsulting\Money\Exceptions\RoundingNecessary;
use RoundlyConsulting\Money\Exceptions\UnknownCurrency;
use RoundlyConsulting\Money\Money;
use RoundlyConsulting\Plausible\DataTransferObjects\Revenue;
use RoundlyConsulting\Plausible\Exceptions\InvalidRevenue;
use RoundlyConsulting\Plausible\Jobs\CaptureEventJob;
use RoundlyConsulting\Plausible\Requests\Events\CaptureEventRequest;

it('serialises a major amount as an exact decimal string and upper-cases the currency', function () {
    expect(Revenue::of('49', 'eur')->toArray())->toBe(['currency' => 'EUR', 'amount' => '49.00']);
});

it('accepts an int major amount', function () {
    expect(Revenue::of(49, 'EUR')->toArray())->toBe(['currency' => 'EUR', 'amount' => '49.00']);
});

it('wraps an existing Money', function () {
    $revenue = Revenue::make(Money::ofMinor(1299, 'USD'));

    expect($revenue->toArray())->toBe(['currency' => 'USD', 'amount' => '12.99'])
        ->and($revenue->amount->minor())->toBe('1299');
});

it('sends an amount beyond int64 exactly', function () {
    expect(Revenue::of('92233720368547758.08', 'USD')->toArray()['amount'])->toBe('92233720368547758.08')
        ->and(Revenue::make(Money::ofMinor('123456789012345678901', 'USD'))->toArray()['amount'])
        ->toBe('1234567890123456789.01');
});

it('respects the currency exponent', function () {
    expect(Revenue::of('1500', 'JPY')->toArray())->toBe(['currency' => 'JPY', 'amount' => '1500'])
        ->and(Revenue::of('1.5', 'BHD')->toArray()['amount'])->toBe('1.500');
});

it('keeps a negative amount (refunds)', function () {
    expect(Revenue::of('-5.10', 'EUR')->toArray()['amount'])->toBe('-5.10');
});

it('rejects an invalid currency code', function () {
    Revenue::of('1', 'EURO');
})->throws(InvalidRevenue::class, 'Invalid revenue currency [EURO]');

it('chains the money exception behind an unknown currency', function () {
    try {
        Revenue::of('1', 'XYZ');
    } catch (InvalidRevenue $e) {
        expect($e->getPrevious())->toBeInstanceOf(UnknownCurrency::class);

        return;
    }

    $this->fail('No InvalidRevenue thrown.');
});

it('rejects a registered custom currency Plausible cannot convert', function () {
    expect(Currency::of('PTS')->iso)->toBeFalse();

    Revenue::make(Money::ofMajor('10', 'PTS'));
})->throws(InvalidRevenue::class, 'Invalid revenue currency [PTS]');

it('rejects a custom currency through of() as well', function () {
    Revenue::of('10', 'PTS');
})->throws(InvalidRevenue::class, 'Invalid revenue currency [PTS]');

it('rejects more precision than the currency allows instead of rounding', function () {
    try {
        Revenue::of('12.999', 'USD');
    } catch (InvalidRevenue $e) {
        expect($e->getMessage())->toStartWith('Invalid revenue amount [12.999]:')
            ->and($e->getPrevious())->toBeInstanceOf(RoundingNecessary::class);

        return;
    }

    $this->fail('No InvalidRevenue thrown.');
});

it('rejects a malformed amount', function (string $amount) {
    Revenue::of($amount, 'EUR');
})->with(['1,50', '1e3', 'abc', ''])->throws(InvalidRevenue::class, 'Invalid revenue amount');

it('includes revenue and interactive in the event payload only when set', function () {
    $withRevenue = CaptureEventRequest::make(
        site: 'localhost',
        url: 'https://localhost/checkout',
        name: 'purchase',
        revenue: Revenue::of('49', 'EUR'),
        interactive: false,
    )->toQuery()->toArray();

    expect($withRevenue)->toHaveKey('revenue', ['currency' => 'EUR', 'amount' => '49.00'])
        ->and($withRevenue)->toHaveKey('interactive', false);

    $plain = CaptureEventRequest::make(
        site: 'localhost',
        url: 'https://localhost/blog',
    )->toQuery()->toArray();

    expect($plain)->not->toHaveKey('revenue')
        ->and($plain)->not->toHaveKey('interactive');
});

it('posts the revenue amount as a JSON string, never a number', function () {
    Http::fake(['*' => Http::response(status: 202)]);

    plausible()
        ->events()
        ->setVisitor('Custom Agent/1.0', '5.178.50.178')
        ->capture(CaptureEventRequest::make(
            site: 'localhost',
            url: 'https://localhost/checkout',
            name: 'purchase',
            revenue: Revenue::of('92233720368547758.08', 'USD'),
        ));

    Http::assertSent(fn (Request $request): bool => str_contains(
        $request->body(),
        '"revenue":{"currency":"USD","amount":"92233720368547758.08"}',
    ));
});

it('survives the queue round-trip inside a capture job', function () {
    $job = new CaptureEventJob(CaptureEventRequest::make(
        site: 'localhost',
        url: 'https://localhost/checkout',
        revenue: Revenue::of('12.99', 'USD'),
    ), 'Custom Agent/1.0', '5.178.50.178');

    /** @var CaptureEventJob $restored */
    $restored = unserialize(serialize($job));

    expect($restored->request->revenue?->toArray())->toBe(['currency' => 'USD', 'amount' => '12.99']);
});
