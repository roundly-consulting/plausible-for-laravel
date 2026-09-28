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
                ->delete('/api/v1/sites/'.self::segment($site)),
        )->json('deleted') !== false;
    }

    public function get(string $site): SiteResult
    {
        return SiteResult::fromResponse(
            $this->throttled(
                'sites',
                $site,
                fn (): Response => $this->request(throw: false)
                    ->get('/api/v1/sites/'.self::segment($site)),
            ),
        );
    }

    /**
     * List every site the key's team owns, following Plausible's `meta.after` cursor
     * through all pages (100 sites per page).
     *
     * @return list<SiteResult>
     */
    public function list(): array
    {
        return array_map(
            /** @param array<string, mixed> $site */
            static fn (array $site): SiteResult => SiteResult::fromArray($site),
            $this->paginate('/api/v1/sites', 'sites', null),
        );
    }

    public function update(UpdateSiteRequest $request): SiteResult
    {
        return SiteResult::fromResponse(
            $this->throttled(
                'sites',
                $request->site,
                fn (): Response => $this->request(throw: false)
                    ->put('/api/v1/sites/'.self::segment($request->site), $request->toQuery()->toArray()),
            ),
        );
    }
}
