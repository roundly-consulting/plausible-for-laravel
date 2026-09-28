<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Testing;

use RoundlyConsulting\Plausible\Repositories\GoalsRepository;
use RoundlyConsulting\Plausible\Requests\Sites\CreateGoalRequest;
use RoundlyConsulting\Plausible\Results\GoalResult;

/**
 * Recording {@see GoalsRepository} used by {@see PlausibleFake}. `delete()` sends nothing
 * and answers `true`; `create()` runs against the fake's stubbed HTTP layer.
 *
 * @internal
 */
final class FakeGoalsRepository extends GoalsRepository
{
    public function __construct(
        private readonly PlausibleFake $fake,
    ) {}

    public function create(CreateGoalRequest $request): GoalResult
    {
        $this->fake->recordGoalCreated($request);

        return parent::create($request);
    }

    public function delete(string $site, int|string $goalId): bool
    {
        $this->fake->recordGoalDeleted($site, $goalId);

        return true;
    }
}
