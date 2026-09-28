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
     * List every goal of a site, following Plausible's `meta.after` cursor through all
     * pages (100 goals per page).
     *
     * @return list<GoalResult>
     */
    public function list(string $site): array
    {
        return array_map(
            /** @param array<string, mixed> $goal */
            static fn (array $goal): GoalResult => GoalResult::fromArray($goal),
            $this->paginate('/api/v1/sites/goals', 'goals', $site, ['site_id' => $site]),
        );
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
                ->delete('/api/v1/sites/goals/'.self::segment($goalId), ['site_id' => $site]),
        )->json('deleted') !== false;
    }
}
