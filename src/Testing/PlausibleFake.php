<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Testing;

use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Assert;
use RoundlyConsulting\Plausible\Plausible;
use RoundlyConsulting\Plausible\Repositories\EventsRepository;
use RoundlyConsulting\Plausible\Repositories\GoalsRepository;
use RoundlyConsulting\Plausible\Repositories\SharedLinksRepository;
use RoundlyConsulting\Plausible\Repositories\SitesRepository;
use RoundlyConsulting\Plausible\Repositories\StatsRepository;
use RoundlyConsulting\Plausible\Requests\Events\CaptureEventRequest;
use RoundlyConsulting\Plausible\Requests\Sites\CreateGoalRequest;
use RoundlyConsulting\Plausible\Requests\Sites\CreateSharedLinkRequest;
use RoundlyConsulting\Plausible\Requests\Sites\CreateSiteRequest;
use RoundlyConsulting\Plausible\Requests\Sites\UpdateSiteRequest;

/**
 * A recording, still-offline variant of {@see Plausible} for host-app tests.
 * Calls run as usual but hit a faked HTTP layer, while the fake records intent
 * so assertions can verify it — mirroring Laravel's `*::fake()` ergonomics (see
 * {@see Plausible::fake()}).
 *
 * It lives in src/ so host apps can use it; it depends only on PHPUnit's Assert,
 * always present in a Laravel app's dev dependencies.
 */
final class PlausibleFake extends Plausible
{
    /** @var list<CaptureEventRequest> */
    private array $capturedEvents = [];

    /** @var list<array{type: string, request: mixed}> */
    private array $queries = [];

    /** @var list<CreateSiteRequest> */
    private array $sitesCreated = [];

    /** @var list<string> */
    private array $sitesDeleted = [];

    /** @var list<UpdateSiteRequest> */
    private array $sitesUpdated = [];

    /** @var list<CreateGoalRequest> */
    private array $goalsCreated = [];

    /** @var list<string> */
    private array $goalsDeleted = [];

    /** @var list<CreateSharedLinkRequest> */
    private array $sharedLinksCreated = [];

    public function __construct()
    {
        if (! is_string(config('plausible.url')) || config('plausible.url') === '') {
            config(['plausible.url' => 'https://plausible.test']);
        }

        if (! is_string(config('plausible.key')) || config('plausible.key') === '') {
            config(['plausible.key' => 'fake-key']);
        }

        Http::fake([
            rtrim((string) config('plausible.url'), '/').'/*' => Http::response(['results' => []], 200),
        ]);
    }

    public function stats(): StatsRepository
    {
        return new FakeStatsRepository($this);
    }

    public function events(): EventsRepository
    {
        return new FakeEventsRepository($this);
    }

    public function sites(): SitesRepository
    {
        return new FakeSitesRepository($this);
    }

    public function goals(): GoalsRepository
    {
        return new FakeGoalsRepository($this);
    }

    public function sharedLinks(): SharedLinksRepository
    {
        return new FakeSharedLinksRepository($this);
    }

    public function recordEvent(CaptureEventRequest $request): void
    {
        $this->capturedEvents[] = $request;
    }

    public function recordQuery(string $type, mixed $request): void
    {
        $this->queries[] = ['type' => $type, 'request' => $request];
    }

    public function recordSiteCreated(CreateSiteRequest $request): void
    {
        $this->sitesCreated[] = $request;
    }

    public function recordSiteDeleted(string $site): void
    {
        $this->sitesDeleted[] = $site;
    }

    public function recordSiteUpdated(UpdateSiteRequest $request): void
    {
        $this->sitesUpdated[] = $request;
    }

    public function recordGoalCreated(CreateGoalRequest $request): void
    {
        $this->goalsCreated[] = $request;
    }

    public function recordGoalDeleted(string $site, int|string $goalId): void
    {
        $this->goalsDeleted[] = $site.'|'.$goalId;
    }

    public function recordSharedLinkCreated(CreateSharedLinkRequest $request): void
    {
        $this->sharedLinksCreated[] = $request;
    }

    public function assertEventCaptured(?callable $filter = null): void
    {
        if ($filter === null) {
            Assert::assertNotEmpty($this->capturedEvents, 'Expected an event to be captured, but none were.');

            return;
        }

        $matched = false;

        foreach ($this->capturedEvents as $event) {
            if ($filter($event) === true) {
                $matched = true;

                break;
            }
        }

        Assert::assertTrue($matched, 'Expected a captured event matching the callback, but none did.');
    }

    public function assertEventNotCaptured(?callable $filter = null): void
    {
        if ($filter === null) {
            Assert::assertEmpty($this->capturedEvents, 'Expected no events to be captured, but some were.');

            return;
        }

        foreach ($this->capturedEvents as $event) {
            Assert::assertFalse($filter($event) === true, 'Expected no captured event to match the callback, but one did.');
        }
    }

    public function assertNothingCaptured(): void
    {
        Assert::assertEmpty($this->capturedEvents, 'Expected nothing to be captured.');
    }

    public function assertEventCapturedCount(int $count): void
    {
        Assert::assertCount($count, $this->capturedEvents, "Expected [{$count}] events to be captured.");
    }

    public function assertQueried(?callable $filter = null): void
    {
        if ($filter === null) {
            Assert::assertNotEmpty($this->queries, 'Expected a stats query, but none were made.');

            return;
        }

        $matched = false;

        foreach ($this->queries as $query) {
            if ($filter($query['request'], $query['type']) === true) {
                $matched = true;

                break;
            }
        }

        Assert::assertTrue($matched, 'Expected a stats query matching the callback, but none did.');
    }

    public function assertNothingQueried(): void
    {
        Assert::assertEmpty($this->queries, 'Expected no stats queries, but some were made.');
    }

    public function assertSiteCreated(?string $domain = null): void
    {
        if ($domain === null) {
            Assert::assertNotEmpty($this->sitesCreated, 'Expected a site to be created, but none were.');

            return;
        }

        Assert::assertContains(
            $domain,
            array_map(static fn (CreateSiteRequest $request): string => $request->domain, $this->sitesCreated),
            "Expected site [{$domain}] to be created, but it was not.",
        );
    }

    public function assertSiteDeleted(string $domain): void
    {
        Assert::assertContains($domain, $this->sitesDeleted, "Expected site [{$domain}] to be deleted, but it was not.");
    }

    public function assertSiteUpdated(string $domain): void
    {
        Assert::assertContains(
            $domain,
            array_map(static fn (UpdateSiteRequest $request): string => $request->site, $this->sitesUpdated),
            "Expected site [{$domain}] to be updated, but it was not.",
        );
    }

    public function assertGoalCreated(?string $site = null): void
    {
        if ($site === null) {
            Assert::assertNotEmpty($this->goalsCreated, 'Expected a goal to be created, but none were.');

            return;
        }

        Assert::assertContains(
            $site,
            array_map(static fn (CreateGoalRequest $request): string => $request->site, $this->goalsCreated),
            "Expected a goal to be created for site [{$site}], but none was.",
        );
    }

    public function assertGoalDeleted(string $site, int|string $goalId): void
    {
        Assert::assertContains(
            $site.'|'.$goalId,
            $this->goalsDeleted,
            "Expected goal [{$goalId}] on site [{$site}] to be deleted, but it was not.",
        );
    }

    public function assertSharedLinkCreated(?string $site = null): void
    {
        if ($site === null) {
            Assert::assertNotEmpty($this->sharedLinksCreated, 'Expected a shared link to be created, but none were.');

            return;
        }

        Assert::assertContains(
            $site,
            array_map(static fn (CreateSharedLinkRequest $request): string => $request->site, $this->sharedLinksCreated),
            "Expected a shared link to be created for site [{$site}], but none was.",
        );
    }
}
