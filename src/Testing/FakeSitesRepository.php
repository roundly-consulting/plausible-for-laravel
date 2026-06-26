<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Testing;

use Illuminate\Http\Client\Response;
use RoundlyConsulting\Plausible\Repositories\SitesRepository;
use RoundlyConsulting\Plausible\Requests\Sites\CreateSiteRequest;
use RoundlyConsulting\Plausible\Requests\Sites\UpdateSiteRequest;
use RoundlyConsulting\Plausible\Results\SiteResult;

/**
 * Recording {@see SitesRepository} used by {@see PlausibleFake}.
 */
final class FakeSitesRepository extends SitesRepository
{
    public function __construct(
        private readonly PlausibleFake $fake,
    ) {}

    public function create(CreateSiteRequest $request): Response
    {
        $this->fake->recordSiteCreated($request);

        return parent::create($request);
    }

    public function delete(string $site): Response
    {
        $this->fake->recordSiteDeleted($site);

        return parent::delete($site);
    }

    public function update(UpdateSiteRequest $request): SiteResult
    {
        $this->fake->recordSiteUpdated($request);

        return parent::update($request);
    }
}
