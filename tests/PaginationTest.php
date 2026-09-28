<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use RoundlyConsulting\Plausible\Results\GoalResult;
use RoundlyConsulting\Plausible\Results\SiteResult;

/**
 * Plausible's Sites API list endpoints are cursor-paginated (100 per page): the response's
 * `meta.after` is the cursor for the next page, `null` on the last one.
 */
function pagedResponse(string $key, array $firstPage, array $secondPage): Closure
{
    return static function (Request $request) use ($key, $firstPage, $secondPage) {
        return ($request['after'] ?? null) === 'cursor-1'
            ? Http::response([$key => $secondPage, 'meta' => ['after' => null, 'before' => 'cursor-0', 'limit' => 100]])
            : Http::response([$key => $firstPage, 'meta' => ['after' => 'cursor-1', 'before' => null, 'limit' => 100]]);
    };
}

it('follows meta.after through every page of sites', function () {
    Http::fake(['*' => pagedResponse(
        'sites',
        [['domain' => 'a.test', 'timezone' => 'UTC']],
        [['domain' => 'b.test', 'timezone' => 'UTC']],
    )]);

    $sites = plausible()->sites()->list();

    expect($sites)->toHaveCount(2)
        ->and(array_map(fn (SiteResult $site) => $site->domain, $sites))->toBe(['a.test', 'b.test']);

    Http::assertSentCount(2);
    Http::assertSent(fn ($request) => ($request['after'] ?? null) === 'cursor-1');
});

it('follows meta.after through every page of goals', function () {
    Http::fake(['*' => pagedResponse(
        'goals',
        [['id' => 1, 'goal_type' => 'event', 'event_name' => 'Signup']],
        [['id' => 2, 'goal_type' => 'page', 'page_path' => '/checkout']],
    )]);

    $goals = plausible()->goals()->list('localhost');

    expect(array_map(fn (GoalResult $goal) => $goal->id, $goals))->toBe([1, 2]);

    Http::assertSentCount(2);
    Http::assertSent(fn ($request) => ($request['after'] ?? null) === 'cursor-1'
        && $request['site_id'] === 'localhost');
});

it('stops when the server repeats a cursor', function () {
    Http::fake(['*' => Http::response([
        'sites' => [['domain' => 'loop.test', 'timezone' => 'UTC']],
        'meta' => ['after' => 'same', 'before' => null, 'limit' => 100],
    ])]);

    $sites = plausible()->sites()->list();

    expect($sites)->toHaveCount(2);

    Http::assertSentCount(2);
});
