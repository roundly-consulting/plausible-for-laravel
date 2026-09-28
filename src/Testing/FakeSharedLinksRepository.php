<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Testing;

use RoundlyConsulting\Plausible\Repositories\SharedLinksRepository;
use RoundlyConsulting\Plausible\Requests\Sites\CreateSharedLinkRequest;
use RoundlyConsulting\Plausible\Results\SharedLinkResult;

/**
 * Recording {@see SharedLinksRepository} used by {@see PlausibleFake}.
 *
 * @internal
 */
final class FakeSharedLinksRepository extends SharedLinksRepository
{
    public function __construct(
        private readonly PlausibleFake $fake,
    ) {}

    public function createFrom(CreateSharedLinkRequest $request): SharedLinkResult
    {
        $this->fake->recordSharedLinkCreated($request);

        return parent::createFrom($request);
    }
}
