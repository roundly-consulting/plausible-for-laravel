<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use RoundlyConsulting\Plausible\Support\ScriptTag;

beforeEach(fn () => config()->set('plausible.script.domain', 'scripted.test'));

it('renders the hosted script tag from config', function () {
    $html = (string) ScriptTag::render();

    expect($html)->toContain('data-domain="scripted.test"')
        ->toContain('src="https://plausible.io/js/script.js"')
        ->toContain('defer');
});

it('falls back to the configured site for the domain', function () {
    config()->set('plausible.script.domain', null);
    config()->set('plausible.site', 'fallback.test');

    expect((string) ScriptTag::render())->toContain('data-domain="fallback.test"');
});

it('returns empty when no domain is available', function () {
    config()->set('plausible.script.domain', null);
    config()->set('plausible.site', null);

    expect((string) ScriptTag::render())->toBe('');
});

it('overrides the domain explicitly', function () {
    expect((string) ScriptTag::render('explicit.test'))->toContain('data-domain="explicit.test"');
});

it('uses the configured script src', function () {
    config()->set('plausible.script.src', 'https://analytics.example.com/js/script.js');

    expect((string) ScriptTag::render())->toContain('src="https://analytics.example.com/js/script.js"');
});

it('falls back to the default src when the configured src is empty', function () {
    config()->set('plausible.script.src', '');

    expect((string) ScriptTag::render())->toContain('src="https://plausible.io/js/script.js"');
});

it('renders via the blade directive', function () {
    expect(Blade::render('@plausibleScript'))->toContain('data-domain="scripted.test"');
});

it('renders via the blade directive with an explicit domain', function () {
    expect(Blade::render("@plausibleScript('arg.test')"))->toContain('data-domain="arg.test"');
});

it('renders via the blade component', function () {
    expect(Blade::render('<x-plausible-script />'))->toContain('data-domain="scripted.test"');
});

it('renders the component with a domain attribute', function () {
    expect(Blade::render('<x-plausible-script domain="attr.test" />'))->toContain('data-domain="attr.test"');
});
