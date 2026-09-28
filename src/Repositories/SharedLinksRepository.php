<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Repositories;

use Illuminate\Http\Client\Response;
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
    /**
     * Find or create the shared link `$name` for a site. Plausible answers with the existing
     * link when one of that name exists.
     */
    public function create(string $site, string $name): SharedLinkResult
    {
        return $this->createFrom(CreateSharedLinkRequest::make($site, $name));
    }

    public function createFrom(CreateSharedLinkRequest $request): SharedLinkResult
    {
        return SharedLinkResult::fromResponse(
            $this->throttled(
                'sites',
                $request->site,
                fn (): Response => $this->request(throw: false)
                    ->put('/api/v1/sites/shared-links', $request->toQuery()->toArray()),
            ),
        );
    }
}
