<?php

declare(strict_types=1);

use RoundlyConsulting\Plausible\Exceptions\PlausibleException;
use RoundlyConsulting\Plausible\Plausible;
use RoundlyConsulting\Plausible\Repositories\EventsRepository;
use RoundlyConsulting\Plausible\Repositories\GoalsRepository;
use RoundlyConsulting\Plausible\Repositories\SharedLinksRepository;
use RoundlyConsulting\Plausible\Repositories\SitesRepository;
use RoundlyConsulting\Plausible\Repositories\StatsRepository;
use RoundlyConsulting\Testing\Arch\ArchPresets;

/**
 * The arch presets. Plausible shipped **no arch file at all** before this row.
 */
ArchPresets::strictTypes('RoundlyConsulting\Plausible');

/**
 * Exempt from finality, each deliberately:
 *  - the five repositories — the package's own Fake* counterparts in src/Testing extend
 *    them, which is how `Plausible::fake()` works. `final` would break a feature this
 *    package ships.
 *  - Plausible — PlausibleFake extends it, same reason.
 *  - PlausibleException — the base every plausible error extends, so a host can catch the
 *    whole surface with one type.
 */
ArchPresets::finalByDefault('RoundlyConsulting\Plausible')
    ->ignoring([
        Plausible::class,
        StatsRepository::class,
        EventsRepository::class,
        SitesRepository::class,
        GoalsRepository::class,
        SharedLinksRepository::class,
        PlausibleException::class,
    ]);

/**
 * `swappableModelsAreNotFinal` and `modelsResolveThroughSeam` are deliberately NOT adopted,
 * and this is a rejection with cause rather than an omission.
 *
 * Plausible ships **no Eloquent model and no `*_model`-shaped config key** — it is an API
 * client. Every leaf of `config/plausible.php` is a URL, a credential, a rate-limit bound or
 * a tracking default; nothing names a class a host could subclass. Both halves of
 * `modelsResolveThroughSeam` are therefore inert (there is no seam directory and no model
 * literal to stray), which is the same shape jwt rejected it on, and the fleet's
 * pre-classification rule (`Swap? > 0`) scores this row 0. The spec agrees: `Swap? 0`.
 */

/**
 * Plausible sends an API key as a Bearer token and derives a visitor identifier for
 * server-side tracking. Those are exactly the places a hand-rolled hash or id scheme would
 * plausibly land instead of coming from crypto-for-laravel or the framework.
 */
ArchPresets::noLocalCryptoPrimitives('RoundlyConsulting\Plausible');

/**
 * The Dependency Policy as a test. No `alsoAllow`: plausible's `require` ships only
 * php/illuminate/roundly, and the workflow installs test tooling with `--dev`. If this goes
 * red the graph is wrong — never widen the allow-list to quiet it.
 */
ArchPresets::runtimeRequireIsWhitelisted(__DIR__.'/../composer.json');

ArchPresets::noDebuggingLeftovers();
