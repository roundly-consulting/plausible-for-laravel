<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Repositories;

use RoundlyConsulting\Plausible\Requests\Sites\CreateSharedLinkRequest;
use RoundlyConsulting\Plausible\Results\SharedLinkResult;

/**
 * Shared dashboard links via the Plausible Sites API.
 *
 * Note: shared links are gated to enterprise plans on Plausible Cloud (and
 * require the corresponding feature on self-hosted instances).
 */
class SharedLinksRepository extends Repository
{
    public function create(string $site, string $name, ?string $password = null): SharedLinkResult
    {
        return $this->createFrom(CreateSharedLinkRequest::make($site, $name, $password));
    }

    public function createFrom(CreateSharedLinkRequest $request): SharedLinkResult
    {
        return SharedLinkResult::fromResponse(
            $this->request()->put('/api/v1/sites/shared-links', $request->toQuery()->toArray()),
        );
    }
}
