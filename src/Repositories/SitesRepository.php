<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Repositories;

use Illuminate\Http\Client\Response;
use RoundlyConsulting\Plausible\Requests\Sites\CreateSiteRequest;
use RoundlyConsulting\Plausible\Requests\Sites\UpdateSiteRequest;
use RoundlyConsulting\Plausible\Results\SiteResult;

class SitesRepository extends Repository
{
    public function create(CreateSiteRequest $request): SiteResult
    {
        return SiteResult::fromResponse(
            $this->throttled(
                'sites',
                $request->domain,
                fn (): Response => $this->request(throw: false)
                    ->post('/api/v1/sites', $request->toQuery()->toArray()),
            ),
        );
    }

    /**
     * Delete a site and all of its data. True unless Plausible answers `deleted: false`
     * (an empty 2xx counts as deleted); a failed request throws.
     */
    public function delete(string $site): bool
    {
        return $this->throttled(
            'sites',
            $site,
            fn (): Response => $this->request(throw: false)
                ->delete('/api/v1/sites/'.$site),
        )->json('deleted') !== false;
    }

    public function get(string $site): SiteResult
    {
        return SiteResult::fromResponse(
            $this->throttled(
                'sites',
                $site,
                fn (): Response => $this->request(throw: false)
                    ->get('/api/v1/sites/'.$site),
            ),
        );
    }

    /**
     * List the sites in the account.
     *
     * @return list<SiteResult>
     */
    public function list(): array
    {
        $sites = $this->throttled(
            'sites',
            null,
            fn (): Response => $this->request(throw: false)->get('/api/v1/sites'),
        )->json('sites');

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
            $this->throttled(
                'sites',
                $request->site,
                fn (): Response => $this->request(throw: false)
                    ->put('/api/v1/sites/'.$request->site, $request->toQuery()->toArray()),
            ),
        );
    }
}
