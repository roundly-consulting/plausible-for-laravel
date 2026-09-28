<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\HtmlString;
use RoundlyConsulting\Plausible\DataTransferObjects\Revenue;
use RoundlyConsulting\Plausible\Exceptions\MissingConfiguration;
use RoundlyConsulting\Plausible\Exceptions\UniqueVisitorIdentifierRequired;
use RoundlyConsulting\Plausible\Facades\Plausible;
use RoundlyConsulting\Plausible\PlausibleManager;
use RoundlyConsulting\Plausible\Repositories\EventsRepository;
use RoundlyConsulting\Plausible\Repositories\GoalsRepository;
use RoundlyConsulting\Plausible\Repositories\SharedLinksRepository;
use RoundlyConsulting\Plausible\Repositories\SitesRepository;
use RoundlyConsulting\Plausible\Repositories\StatsRepository;
use RoundlyConsulting\Plausible\Requests\Sites\CreateSiteRequest;
use RoundlyConsulting\Plausible\Results\HealthResult;
use RoundlyConsulting\Plausible\Results\SiteResult;

/**
 * The public API contract — Manager → Facade (+ fake). Plausible is a remote-API client:
 * every area is a resource client resolved through the container, and the package has no
 * `src/Actions`, so `toReachEveryAction()` has nothing to pin and is deliberately omitted.
 */
it('pins the facade contract', function (): void {
    expect(Plausible::class)
        ->toDocumentItsRoot()
        ->toBeFakeable();
});

it('declares the global alias for the facade and never a core facade name', function (): void {
    /** @var array{extra: array{laravel: array{aliases: array<string, string>}}} $composer */
    $composer = json_decode((string) file_get_contents(__DIR__.'/../../composer.json'), true, flags: JSON_THROW_ON_ERROR);

    expect($composer['extra']['laravel']['aliases'])->toBe(['Plausible' => Plausible::class])
        ->and(class_exists('Illuminate\\Support\\Facades\\Plausible'))->toBeFalse();
});

it('resolves every resource client through the facade', function (): void {
    expect(Plausible::stats())->toBeInstanceOf(StatsRepository::class)
        ->and(Plausible::events())->toBeInstanceOf(EventsRepository::class)
        ->and(Plausible::sites())->toBeInstanceOf(SitesRepository::class)
        ->and(Plausible::goals())->toBeInstanceOf(GoalsRepository::class)
        ->and(Plausible::sharedLinks())->toBeInstanceOf(SharedLinksRepository::class)
        ->and(Plausible::health())->toBeInstanceOf(HealthResult::class);
});

it('resolves resource clients through the container so a host override applies', function (): void {
    $custom = new class extends EventsRepository {};
    app()->instance(EventsRepository::class, $custom);

    expect(Plausible::events())->toBe($custom);
});

it('serves the same API through an injected manager', function (): void {
    config()->set('plausible.site', 'di.test');

    $manager = app(PlausibleManager::class);

    expect($manager)->toBe(app(PlausibleManager::class))
        ->and($manager)->toBe(Plausible::getFacadeRoot())
        ->and($manager)->toBe(plausible())
        ->and((string) $manager->script())->toContain('data-domain="di.test"')
        ->and($manager->sites())->toBeInstanceOf(SitesRepository::class);
});

describe('script', function (): void {
    it('renders the hosted tag for any PHP view', function (): void {
        config()->set('plausible.site', 'site.test');

        $tag = Plausible::script();

        expect($tag)->toBeInstanceOf(HtmlString::class)
            ->and((string) $tag)->toBe('<script defer data-domain="site.test" src="https://plausible.io/js/script.js"></script>');
    });

    it('takes an explicit domain and escapes it', function (): void {
        expect((string) Plausible::script('a"b.test'))->toContain('data-domain="a&quot;b.test"');
    });

    it('is empty without a domain', function (): void {
        config()->set('plausible.site', null);
        config()->set('plausible.script.domain', null);

        expect((string) Plausible::script())->toBe('');
    });

    it('backs the blade directive and component, so a manager override reaches them', function (): void {
        app()->instance(PlausibleManager::class, new class(app()) extends PlausibleManager
        {
            public function script(?string $domain = null): HtmlString
            {
                return new HtmlString('<!-- custom '.($domain ?? 'default').' -->');
            }
        });

        expect(Blade::render("@plausibleScript('x.test')"))->toBe('<!-- custom x.test -->')
            ->and(Blade::render('<x-plausible-script />'))->toBe('<!-- custom default -->');
    });
});

describe('events()->event', function (): void {
    beforeEach(fn () => Http::fake(['*' => Http::response('ok', 202)]));

    it('captures a custom event on the default site', function (): void {
        config()->set('plausible.site', 'default.test');

        Plausible::events()
            ->setVisitor('Agent/1.0', '5.178.50.178')
            ->event('Signup', url: 'https://default.test/register', props: ['plan' => 'pro']);

        Http::assertSent(static fn ($request): bool => str_ends_with($request->url(), '/api/event')
            && $request['name'] === 'Signup'
            && $request['domain'] === 'default.test'
            && $request['url'] === 'https://default.test/register'
            && $request['props'] === ['plan' => 'pro']
            && ! isset($request['revenue'], $request['referrer'], $request['interactive'])
            && $request->header('X-Forwarded-For') === ['5.178.50.178']);
    });

    it('takes revenue, an explicit site, a referrer and a non-interactive flag', function (): void {
        Plausible::events()
            ->setVisitor('Agent/1.0', '5.178.50.178')
            ->event(
                'Purchase',
                url: 'https://shop.test/checkout',
                revenue: Revenue::of('12.34', 'EUR'),
                site: 'shop.test',
                referrer: 'https://news.test',
                interactive: false,
            );

        Http::assertSent(static fn ($request): bool => $request['domain'] === 'shop.test'
            && $request['revenue'] === ['currency' => 'EUR', 'amount' => '12.34']
            && $request['referrer'] === 'https://news.test'
            && $request['interactive'] === false);
    });

    it('refuses without a site', function (): void {
        config()->set('plausible.site', null);

        Plausible::events()->setVisitor('Agent/1.0', '5.178.50.178')->event('Signup', url: 'https://x.test');
    })->throws(MissingConfiguration::class);

    it('needs the visitor, like every capture', function (): void {
        config()->set('plausible.site', 'default.test');

        Plausible::events()->event('Signup', url: 'https://default.test/register');
    })->throws(UniqueVisitorIdentifierRequired::class);
});

describe('typed provisioning results', function (): void {
    it('returns the created site', function (): void {
        useHttpSnapshot('sites/create');

        $site = Plausible::sites()->create(CreateSiteRequest::make(domain: 'lorem.test'));

        expect($site)->toBeInstanceOf(SiteResult::class)
            ->and($site->domain)->toBe('lorem.test');
    });

    it('returns a boolean for site and goal deletions', function (): void {
        useHttpSnapshot('sites/delete');

        expect(Plausible::sites()->delete('lorem.test'))->toBeTrue()
            ->and(Plausible::goals()->delete('lorem.test', 3))->toBeTrue();
    });
});
