{{--
    The Plausible tracking-script tag. Plausible::script(), @plausibleScript and
    <x-plausible-script> all render this view (only when a domain is known), so a copy
    published with `php artisan vendor:publish --tag="plausible-views"` customises every one.
    Variables: $domain (the data-domain) and $src (plausible.script.src).
--}}
<script defer data-domain="{{ $domain }}" src="{{ $src }}"></script>
