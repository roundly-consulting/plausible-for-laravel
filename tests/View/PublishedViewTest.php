<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use RoundlyConsulting\Plausible\Facades\Plausible;

/*
 * `vendor:publish --tag=plausible-views` copies the script view into the host app — the
 * facade, the directive and the component must all render THAT view, or publishing it
 * customises nothing.
 */

beforeEach(function () {
    config()->set('plausible.script.domain', 'custom.test');

    $this->viewDir = sys_get_temp_dir().'/plausible-views-'.uniqid();
    File::ensureDirectoryExists($this->viewDir.'/components');
    File::put(
        $this->viewDir.'/components/script.blade.php',
        '<script data-custom="CUSTOMISED-VIEW" data-domain="{{ $domain }}" src="{{ $src }}"></script>',
    );

    View::prependNamespace('plausible', $this->viewDir);
});

afterEach(fn () => File::deleteDirectory($this->viewDir));

it('renders a published script view from every entry point', function (Closure $render) {
    expect($render())->toBe('<script data-custom="CUSTOMISED-VIEW" data-domain="custom.test" src="https://plausible.io/js/script.js"></script>');
})->with([
    'facade' => [fn () => (string) Plausible::script()],
    'directive' => [fn () => Blade::render('@plausibleScript')],
    'component' => [fn () => Blade::render('<x-plausible-script />')],
]);

it('still renders nothing without a domain', function () {
    config()->set('plausible.script.domain', null);
    config()->set('plausible.site', null);

    expect((string) Plausible::script())->toBe('');
});

it('escapes the domain in the shipped view', function () {
    View::replaceNamespace('plausible', dirname(__DIR__, 2).'/resources/views');

    expect((string) Plausible::script('"><x'))
        ->toBe('<script defer data-domain="&quot;&gt;&lt;x" src="https://plausible.io/js/script.js"></script>');
});
