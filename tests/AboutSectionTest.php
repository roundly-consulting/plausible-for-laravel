<?php

declare(strict_types=1);

/**
 * The secret-safe `about` capture (A).
 *
 * Purchases #13 is the bug this exists for: the fleet's most credential-heavy `about`
 * section was guarded by negative assertions against `app(Kernel::class)->output()`, which
 * returns `''`. Every "does not leak" check was vacuous — passing against empty output.
 *
 * This package holds a real bearer credential: `plausible.key` is sent as an Authorization
 * header on every Stats and Sites request. The provider reports it as SET/MISSING, and a
 * one-line change to that ternary would print it — the exact shape of the bug above, in the
 * one place it matters most here.
 *
 * `mustRender` is required and non-empty, so the negative half can never pass over empty
 * output. Its entries deliberately do not overlap the secret surface.
 */
it('renders the plausible section without leaking the api key', function (): void {
    config()->set('plausible.key', 'plausible-bearer-super-secret-value');
    config()->set('plausible.url', 'https://analytics.internal.example');
    config()->set('plausible.site', 'internal.example');

    expect('plausible')->toLeakNoSecrets(
        secrets: [
            // The bearer token. Never rendered, only reported present.
            'plausible-bearer-super-secret-value',
        ],
        mustRender: [
            // The positive proof the section reports rather than sitting empty — including
            // that the API-key line really renders its presence marker, which is what makes
            // hiding the value meaningful rather than accidental.
            'API key',
            'SET',
            'Instance',
            'Default site',
            'Rate limiting',
            'Queued tracking',
        ],
    );
});
