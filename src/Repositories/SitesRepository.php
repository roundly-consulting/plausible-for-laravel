<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Repositories;

use Illuminate\Http\Client\Response;
use RoundlyConsulting\Plausible\Requests\Sites\CreateSiteRequest;
use RoundlyConsulting\Plausible\Requests\Sites\UpdateSiteRequest;
use RoundlyConsulting\Plausible\Results\SiteResult;

class SitesRepository extends Repository
{
    public function create(CreateSiteRequest $request): Response
    {
        return $this->request()
            ->post('/api/v1/sites', $request->toQuery()->toArray());
    }

    public function delete(string $site): Response
    {
        return $this->request()
            ->delete('/api/v1/sites/'.$site);
    }

    public function get(string $site): SiteResult
    {
        return SiteResult::fromResponse(
            $this->request()->get('/api/v1/sites/'.$site),
        );
    }

    /**
     * List the sites in the account.
     *
     * @return list<SiteResult>
     */
    public function list(): array
    {
        $sites = $this->request()->get('/api/v1/sites')->json('sites');

        if (! is_array($sites)) {
            return [];
        }

        return array_values(array_map(
            /** @param array<string, mixed> $site */
            static fn (array $site): SiteResult => SiteResult::fromArray($site),
            array_filter($sites, 'is_array'),
        ));
    }

    public function update(UpdateSiteRequest $request): SiteResult
    {
        return SiteResult::fromResponse(
            $this->request()->put('/api/v1/sites/'.$request->site, $request->toQuery()->toArray()),
        );
    }
}
