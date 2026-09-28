<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\ExpectationFailedException;
use RoundlyConsulting\Plausible\Facades\Plausible;
use RoundlyConsulting\Plausible\PlausibleManager;
use RoundlyConsulting\Plausible\Requests\Events\CaptureEventRequest;
use RoundlyConsulting\Plausible\Requests\Sites\CreateGoalRequest;
use RoundlyConsulting\Plausible\Requests\Sites\CreateSiteRequest;
use RoundlyConsulting\Plausible\Requests\Sites\UpdateSiteRequest;
use RoundlyConsulting\Plausible\Results\SiteResult;
use RoundlyConsulting\Plausible\Testing\PlausibleFake;
use RoundlyConsulting\Plausible\Tests\Fixtures\TrackedOrder;

it('installs the fake behind the facade and in the container', function () {
    $fake = Plausible::fake();

    expect($fake)->toBeInstanceOf(PlausibleManager::class)
        ->and(Plausible::getFacadeRoot())->toBe($fake)
        ->and(app(PlausibleManager::class))->toBe($fake)
        ->and(plausible())->toBe($fake);
});

it('records a custom event sent through events()->event()', function () {
    config()->set('plausible.site', 'app.test');
    $fake = Plausible::fake();

    Plausible::events()->setVisitor('Agent/1.0', '1.2.3.4')->event('Signup', url: 'https://app.test/register', props: ['plan' => 'pro']);

    $fake->assertEventCaptured(static fn (CaptureEventRequest $event): bool => $event->name === 'Signup'
        && $event->site === 'app.test'
        && $event->props === ['plan' => 'pro']);

    expect(fn () => $fake->assertEventCaptured(static fn (CaptureEventRequest $event): bool => $event->name === 'Login'))
        ->toThrow(ExpectationFailedException::class);
});

it('records events a model fires through the Trackable trait', function (bool $queued) {
    config()->set('plausible.site', 'tracked.test');
    config()->set('plausible.tracking.queue', $queued);
    Schema::create('tracked_orders', function (Blueprint $table) {
        $table->id();
        $table->integer('total')->default(0);
    });

    $fake = Plausible::fake();

    TrackedOrder::create(['total' => 5]);

    $fake->assertEventCaptured(static fn (CaptureEventRequest $event): bool => $event->name === 'Order Placed');
    $fake->assertEventCapturedCount(1);

    expect(fn () => $fake->assertNothingCaptured())->toThrow(ExpectationFailedException::class);

    Schema::drop('tracked_orders');
})->with(['queued' => true, 'sync' => false]);

it('answers provisioning calls with typed results and sends nothing', function () {
    $fake = Plausible::fake();

    $site = Plausible::sites()->create(CreateSiteRequest::make(domain: 'new.test', timezone: 'Europe/Bratislava'));

    expect($site)->toBeInstanceOf(SiteResult::class)
        ->and($site->domain)->toBe('new.test')
        ->and($site->timezone)->toBe('Europe/Bratislava')
        ->and(Plausible::sites()->delete('old.test'))->toBeTrue()
        ->and(Plausible::goals()->delete('new.test', 9))->toBeTrue();

    Http::assertNothingSent();
    $fake->assertSiteCreated('new.test');
    $fake->assertSiteDeleted('old.test');
    $fake->assertGoalDeleted('new.test', 9);
});

it('passes every negative assert on an untouched fake', function () {
    $fake = Plausible::fake();

    $fake->assertNothingCaptured();
    $fake->assertEventNotCaptured();
    $fake->assertEventCapturedCount(0);
    $fake->assertNothingQueried();
    $fake->assertSiteNotCreated();
    $fake->assertSiteNotCreated('a.test');
    $fake->assertSiteNotDeleted();
    $fake->assertSiteNotDeleted('a.test');
    $fake->assertSiteNotUpdated();
    $fake->assertSiteNotUpdated('a.test');
    $fake->assertGoalNotCreated();
    $fake->assertGoalNotCreated('a.test');
    $fake->assertGoalNotDeleted();
    $fake->assertGoalNotDeleted('a.test', 1);
    $fake->assertSharedLinkNotCreated();
    $fake->assertSharedLinkNotCreated('a.test');
});

it('narrows negative asserts to the given site', function () {
    $fake = Plausible::fake();

    Plausible::sites()->create(CreateSiteRequest::make(domain: 'a.test'));
    Plausible::sites()->delete('a.test');
    Plausible::sites()->update(UpdateSiteRequest::make(site: 'a.test', domain: 'b.test'));
    Plausible::goals()->create(CreateGoalRequest::event('a.test', 'Signup'));
    Plausible::goals()->delete('a.test', 7);
    Plausible::sharedLinks()->create('a.test', 'Dashboard');

    $fake->assertSiteNotCreated('other.test');
    $fake->assertSiteNotDeleted('other.test');
    $fake->assertSiteNotUpdated('other.test');
    $fake->assertGoalNotCreated('other.test');
    $fake->assertGoalNotDeleted('other.test');
    $fake->assertGoalNotDeleted('a.test', 8);
    $fake->assertSharedLinkNotCreated('other.test');
});

it('fails each assert when its expectation is not met', function (Closure $act, Closure $assert, string $message) {
    $fake = Plausible::fake();

    $act();

    expect(fn () => $assert($fake))->toThrow(ExpectationFailedException::class, $message);
})->with([
    'assertEventCaptured' => [fn () => null, fn (PlausibleFake $f) => $f->assertEventCaptured(), 'Expected an event to be captured'],
    'assertEventNotCaptured' => [fn () => capturedSignup(), fn (PlausibleFake $f) => $f->assertEventNotCaptured(), 'Expected no events to be captured'],
    'assertNothingCaptured' => [fn () => capturedSignup(), fn (PlausibleFake $f) => $f->assertNothingCaptured(), 'Expected nothing to be captured'],
    'assertEventCapturedCount' => [fn () => capturedSignup(), fn (PlausibleFake $f) => $f->assertEventCapturedCount(2), 'Expected [2] events'],
    'assertQueried' => [fn () => null, fn (PlausibleFake $f) => $f->assertQueried(), 'Expected a stats query'],
    'assertQueried (filter)' => [fn () => Plausible::stats()->realtime('a.test'), fn (PlausibleFake $f) => $f->assertQueried(fn ($r, string $type) => $type === 'aggregate'), 'matching the callback'],
    'assertNothingQueried' => [fn () => Plausible::stats()->realtime('a.test'), fn (PlausibleFake $f) => $f->assertNothingQueried(), 'Expected no stats queries'],
    'assertSiteCreated' => [fn () => null, fn (PlausibleFake $f) => $f->assertSiteCreated(), 'Expected a site to be created'],
    'assertSiteNotCreated' => [fn () => Plausible::sites()->create(CreateSiteRequest::make(domain: 'a.test')), fn (PlausibleFake $f) => $f->assertSiteNotCreated(), 'Expected no site to be created'],
    'assertSiteNotCreated (domain)' => [fn () => Plausible::sites()->create(CreateSiteRequest::make(domain: 'a.test')), fn (PlausibleFake $f) => $f->assertSiteNotCreated('a.test'), 'Expected site [a.test] not to be created'],
    'assertSiteDeleted' => [fn () => null, fn (PlausibleFake $f) => $f->assertSiteDeleted('a.test'), 'Expected site [a.test] to be deleted'],
    'assertSiteNotDeleted' => [fn () => Plausible::sites()->delete('a.test'), fn (PlausibleFake $f) => $f->assertSiteNotDeleted(), 'Expected no site to be deleted'],
    'assertSiteNotDeleted (domain)' => [fn () => Plausible::sites()->delete('a.test'), fn (PlausibleFake $f) => $f->assertSiteNotDeleted('a.test'), 'Expected site [a.test] not to be deleted'],
    'assertSiteUpdated' => [fn () => null, fn (PlausibleFake $f) => $f->assertSiteUpdated('a.test'), 'Expected site [a.test] to be updated'],
    'assertSiteNotUpdated' => [fn () => Plausible::sites()->update(UpdateSiteRequest::make(site: 'a.test', domain: 'b.test')), fn (PlausibleFake $f) => $f->assertSiteNotUpdated(), 'Expected no site to be updated'],
    'assertSiteNotUpdated (domain)' => [fn () => Plausible::sites()->update(UpdateSiteRequest::make(site: 'a.test', domain: 'b.test')), fn (PlausibleFake $f) => $f->assertSiteNotUpdated('a.test'), 'Expected site [a.test] not to be updated'],
    'assertGoalCreated' => [fn () => null, fn (PlausibleFake $f) => $f->assertGoalCreated(), 'Expected a goal to be created'],
    'assertGoalCreated (site)' => [fn () => null, fn (PlausibleFake $f) => $f->assertGoalCreated('a.test'), 'for site [a.test]'],
    'assertGoalNotCreated' => [fn () => Plausible::goals()->create(CreateGoalRequest::event('a.test', 'Signup')), fn (PlausibleFake $f) => $f->assertGoalNotCreated(), 'Expected no goal to be created'],
    'assertGoalNotCreated (site)' => [fn () => Plausible::goals()->create(CreateGoalRequest::event('a.test', 'Signup')), fn (PlausibleFake $f) => $f->assertGoalNotCreated('a.test'), 'for site [a.test], but one was'],
    'assertGoalDeleted' => [fn () => null, fn (PlausibleFake $f) => $f->assertGoalDeleted('a.test', 7), 'Expected goal [7] on site [a.test]'],
    'assertGoalNotDeleted' => [fn () => Plausible::goals()->delete('a.test', 7), fn (PlausibleFake $f) => $f->assertGoalNotDeleted(), 'Expected no goal to be deleted'],
    'assertGoalNotDeleted (site+id)' => [fn () => Plausible::goals()->delete('a.test', 7), fn (PlausibleFake $f) => $f->assertGoalNotDeleted('a.test', 7), 'Expected no matching goal'],
    'assertSharedLinkCreated' => [fn () => null, fn (PlausibleFake $f) => $f->assertSharedLinkCreated(), 'Expected a shared link to be created'],
    'assertSharedLinkCreated (site)' => [fn () => null, fn (PlausibleFake $f) => $f->assertSharedLinkCreated('a.test'), 'for site [a.test], but none was'],
    'assertSharedLinkNotCreated' => [fn () => Plausible::sharedLinks()->create('a.test', 'Dashboard'), fn (PlausibleFake $f) => $f->assertSharedLinkNotCreated(), 'Expected no shared link to be created'],
    'assertSharedLinkNotCreated (site)' => [fn () => Plausible::sharedLinks()->create('a.test', 'Dashboard'), fn (PlausibleFake $f) => $f->assertSharedLinkNotCreated('a.test'), 'for site [a.test], but one was'],
]);

function capturedSignup(): void
{
    Plausible::events()
        ->setVisitor('Agent/1.0', '1.2.3.4')
        ->capture(CaptureEventRequest::make(site: 'a.test', url: 'https://a.test/x', name: 'signup'));
}
