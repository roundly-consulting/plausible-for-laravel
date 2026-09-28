<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Repositories;

use Illuminate\Http\Client\Response;
use RoundlyConsulting\Plausible\Requests\Sites\CreateGoalRequest;
use RoundlyConsulting\Plausible\Results\GoalResult;

/**
 * Goals provisioning against the Plausible Sites API.
 *
 * Note: the Sites Provisioning API is gated to enterprise plans on Plausible
 * Cloud (and requires the corresponding feature on self-hosted instances).
 */
class GoalsRepository extends Repository
{
    public function create(CreateGoalRequest $request): GoalResult
    {
        return GoalResult::fromResponse(
            $this->throttled(
                'sites',
                $request->site,
                fn (): Response => $this->request(throw: false)
                    ->put('/api/v1/sites/goals', $request->toQuery()->toArray()),
            ),
        );
    }

    /**
     * @return list<GoalResult>
     */
    public function list(string $site): array
    {
        $goals = $this->throttled(
            'sites',
            $site,
            fn (): Response => $this->request(throw: false)
                ->get('/api/v1/sites/goals', ['site_id' => $site]),
        )->json('goals');

        if (! is_array($goals)) {
            return [];
        }

        return array_values(array_map(
            /** @param array<string, mixed> $goal */
            static fn (array $goal): GoalResult => GoalResult::fromArray($goal),
            array_filter($goals, 'is_array'),
        ));
    }

    /**
     * Delete a goal. True unless Plausible answers `deleted: false`
     * (an empty 2xx counts as deleted); a failed request throws.
     */
    public function delete(string $site, int|string $goalId): bool
    {
        return $this->throttled(
            'sites',
            $site,
            fn (): Response => $this->request(throw: false)
                ->delete('/api/v1/sites/goals/'.$goalId, ['site_id' => $site]),
        )->json('deleted') !== false;
    }
}
