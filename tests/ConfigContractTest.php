<?php

declare(strict_types=1);

/**
 * The config contract, pinned in both directions:
 *
 *  - forward — every key the code reads is shipped. This is shops #18, whose entire
 *    store-credit feature read `shops.payments.*` while the file shipped `payment.*`; 330
 *    tests stayed green because the suite set the same wrong key.
 *  - reverse — every shipped leaf is read. A documented key nothing reads is dead config
 *    that lies to the host: media #27's size cap that never applied, alerts #24's
 *    thrice-documented `escalation` key. Plausible's `rate_limits` block is 24 leaves of
 *    exactly that risk — a host tuning `adaptive` or `jitter` on a key no limiter consults
 *    would get pacing it never asked for and no error.
 */
it('ships exactly the config keys it reads', function (): void {
    expect(__DIR__.'/../config/plausible.php')->toSatisfyConfigContract(__DIR__.'/../src', [
        // `rateLimiter()` reads a whole surface block into `$config` and then indexes it with
        // LITERAL offsets ($config['adaptive'], $config['jitter'], …). Declaring the variable
        // is what turns those offsets into per-leaf proof — without it the eight leaf names
        // would only be covered by a parent read, which the reverse direction deliberately
        // does not accept.
        //
        // The three surface blocks are identical in shape and read by the same method, so the
        // variable can only be mapped to one of them. Mapping `stats` proves the leaf SET;
        // the events/sites twins are allow-listed below. This is not a hole: allowUnread is
        // exact-match, so a NEW dead leaf under events/sites still goes red, and deleting a
        // read (say `$config['jitter']`) reds `stats.jitter` immediately.
        'sectionVariables' => [
            'InteractsWithRateLimits.php' => [
                '$config' => 'plausible.rate_limits.stats',
            ],
            // `PlausibleConfig::rateLimit()` is handed the same block as `$config` and reads
            // the strictly-validated leaves (per, limit, owner, max_wait, jitter) from it.
            'PlausibleConfig.php' => [
                '$config' => 'plausible.rate_limits.stats',
            ],
        ],

        // Read through PlausibleConfig's strict string reader, which names each key as a
        // literal argument rather than a `config()` token.
        'extraReadPrefixes' => [
            'plausible.script.src',
            'plausible.tracking.ip_address',
            'plausible.tracking.user_agent',
        ],

        // The events/sites twins of the eight leaves proven against `stats` above. They are
        // read by the same lines through the same variable; only one section can be mapped.
        'allowUnread' => [
            'plausible.rate_limits.events.enabled',
            'plausible.rate_limits.events.owner',
            'plausible.rate_limits.events.limit',
            'plausible.rate_limits.events.per',
            'plausible.rate_limits.events.adaptive',
            'plausible.rate_limits.events.max_wait',
            'plausible.rate_limits.events.jitter',
            'plausible.rate_limits.events.per_site',
            'plausible.rate_limits.sites.enabled',
            'plausible.rate_limits.sites.owner',
            'plausible.rate_limits.sites.limit',
            'plausible.rate_limits.sites.per',
            'plausible.rate_limits.sites.adaptive',
            'plausible.rate_limits.sites.max_wait',
            'plausible.rate_limits.sites.jitter',
            'plausible.rate_limits.sites.per_site',
        ],
    ]);
});
