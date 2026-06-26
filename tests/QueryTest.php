<?php

declare(strict_types=1);

use RoundlyConsulting\Plausible\Requests\Query;

it('does not set a key when the condition is false', function () {
    $query = Query::make()->when(false, 'foo', 'bar');

    expect($query->toArray())->toBe([]);
});

it('sets a key when the condition is true', function () {
    $query = Query::make()->when(true, 'foo', 'bar');

    expect($query->toArray())->toBe(['foo' => 'bar']);
});

it('resolves a closure value only when the condition is true', function () {
    $invoked = false;

    Query::make()->when(false, 'foo', function () use (&$invoked) {
        $invoked = true;

        return 'bar';
    });

    expect($invoked)->toBeFalse();

    $query = Query::make()->when(true, 'foo', fn (): string => 'bar');

    expect($query->toArray())->toBe(['foo' => 'bar']);
});

it('overwrites an existing key with set', function () {
    $query = Query::make(['foo' => 'one'])->set('foo', 'two');

    expect($query->toArray())->toBe(['foo' => 'two']);
});
