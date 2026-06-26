<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Repositories;

use Illuminate\Http\Client\Response;
use RoundlyConsulting\Plausible\Requests\Sites\CreateSiteRequest;

final class SitesRepository extends Repository
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
}
