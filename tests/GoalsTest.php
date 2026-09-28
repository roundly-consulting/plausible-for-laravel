<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use RoundlyConsulting\Plausible\Enums\GoalType;
use RoundlyConsulting\Plausible\Requests\Sites\CreateGoalRequest;
use RoundlyConsulting\Plausible\Results\GoalResult;

it('creates an event goal', function () {
    useHttpSnapshot('sites/goal');

    $goal = plausible()->goals()->create(CreateGoalRequest::event('localhost', 'Signup'));

    expect($goal)->toBeInstanceOf(GoalResult::class)
        ->and($goal->id)->toBe(42)
        ->and($goal->goalType)->toBe('event')
        ->and($goal->eventName)->toBe('Signup');

    Http::assertSent(function ($request) {
        $body = $request->data();

        return $request->method() === 'PUT'
            && str_contains($request->url(), '/api/v1/sites/goals')
            && $body['site_id'] === 'localhost'
            && $body['goal_type'] === 'event'
            && $body['event_name'] === 'Signup';
    });
});

it('builds a page goal request', function () {
    useHttpSnapshot('sites/goal');

    plausible()->goals()->create(CreateGoalRequest::page('localhost', '/checkout'));

    Http::assertSent(function ($request) {
        $body = $request->data();

        return $body['goal_type'] === 'page' && $body['page_path'] === '/checkout';
    });
});

it('accepts a GoalType enum on the request', function () {
    $request = new CreateGoalRequest('localhost', GoalType::Event, eventName: 'Purchase');

    expect($request->toQuery()->toArray()['goal_type'])->toBe('event');
});

it('lists goals for a site', function () {
    useHttpSnapshot('sites/goals-list');

    $goals = plausible()->goals()->list('localhost');

    expect($goals)->toHaveCount(2)
        ->and($goals[0])->toBeInstanceOf(GoalResult::class)
        ->and($goals[1]->pagePath)->toBe('/checkout');

    Http::assertSent(
        fn ($request) => str_contains($request->url(), '/api/v1/sites/goals')
            && $request['site_id'] === 'localhost',
    );
});

it('returns an empty list when the goals payload is missing', function () {
    Http::fake(['*' => Http::response(['nope' => true], 200)]);

    expect(plausible()->goals()->list('localhost'))->toBe([]);
});

it('deletes a goal', function () {
    Http::fake(['*' => Http::response('', 204)]);

    $deleted = plausible()->goals()->delete('localhost', 42);

    expect($deleted)->toBeTrue();

    Http::assertSent(
        fn ($request) => $request->method() === 'DELETE'
            && str_contains($request->url(), '/api/v1/sites/goals/42'),
    );
});

it('parses goal results from arbitrary arrays', function () {
    $goal = GoalResult::fromArray([]);

    expect($goal->id)->toBeNull()
        ->and($goal->goalType)->toBeNull()
        ->and($goal->eventName)->toBeNull()
        ->and($goal->pagePath)->toBeNull();
});

it('reads the deleted flag of a goal deletion', function () {
    Http::fake(['*' => Http::sequence()->push(['deleted' => true])->push(['deleted' => false])]);

    expect(plausible()->goals()->delete('localhost', 1))->toBeTrue()
        ->and(plausible()->goals()->delete('localhost', 2))->toBeFalse();
});
