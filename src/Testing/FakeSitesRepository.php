<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Testing;

use RoundlyConsulting\Plausible\Repositories\SitesRepository;
use RoundlyConsulting\Plausible\Requests\Sites\CreateSiteRequest;
use RoundlyConsulting\Plausible\Requests\Sites\UpdateSiteRequest;
use RoundlyConsulting\Plausible\Results\SiteResult;

/**
 * Recording {@see SitesRepository} used by {@see PlausibleFake}. `create()` and `delete()`
 * send nothing and answer with the typed result a successful call would give; `update()`
 * runs against the fake's stubbed HTTP layer.
 *
 * @internal
 */
final class FakeSitesRepository extends SitesRepository
{
    public function __construct(
        private readonly PlausibleFake $fake,
    ) {}

    public function create(CreateSiteRequest $request): SiteResult
    {
        $this->fake->recordSiteCreated($request);

        return SiteResult::fromArray(['domain' => $request->domain, 'timezone' => $request->timezone]);
    }

    public function delete(string $site): bool
    {
        $this->fake->recordSiteDeleted($site);

        return true;
    }

    public function update(UpdateSiteRequest $request): SiteResult
    {
        $this->fake->recordSiteUpdated($request);

        return parent::update($request);
    }
}
