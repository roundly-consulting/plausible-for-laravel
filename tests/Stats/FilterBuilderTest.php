<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use RoundlyConsulting\Plausible\Enums\Dimension;
use RoundlyConsulting\Plausible\Enums\FilterOperator;
use RoundlyConsulting\Plausible\Enums\Metric;

it('compiles where filters into the v2 array form', function () {
    useHttpSnapshot('stats/query');

    plausible()->stats()
        ->for('localhost')
        ->metrics(Metric::Visitors)
        ->where(Dimension::VisitBrowser, FilterOperator::Is, 'Chrome')
        ->where('visit:os', 'is', ['Mac', 'Windows'])
        ->run();

    Http::assertSent(function ($request) {
        $filters = $request->data()['filters'];

        return $filters[0] === ['is', 'visit:browser', ['Chrome']]
            && $filters[1] === ['is', 'visit:os', ['Mac', 'Windows']];
    });
});

it('groups the previous filter with orWhere under an or', function () {
    useHttpSnapshot('stats/query');

    plausible()->stats()
        ->for('localhost')
        ->metrics(Metric::Visitors)
        ->where(Dimension::VisitBrowser, FilterOperator::Is, 'Chrome')
        ->orWhere(Dimension::VisitCountry, FilterOperator::Is, 'US')
        ->run();

    Http::assertSent(function ($request) {
        $filters = $request->data()['filters'];

        return count($filters) === 1
            && $filters[0] === ['or', [
                ['is', 'visit:browser', ['Chrome']],
                ['is', 'visit:country', ['US']],
            ]];
    });
});

it('extends an existing or group with further orWhere calls', function () {
    useHttpSnapshot('stats/query');

    plausible()->stats()
        ->for('localhost')
        ->metrics(Metric::Visitors)
        ->where('visit:browser', 'is', 'Chrome')
        ->orWhere('visit:browser', 'is', 'Firefox')
        ->orWhere('visit:browser', 'is', 'Safari')
        ->run();

    Http::assertSent(function ($request) {
        $group = $request->data()['filters'][0];

        return $group[0] === 'or' && count($group[1]) === 3;
    });
});

it('treats a leading orWhere as a plain where', function () {
    useHttpSnapshot('stats/query');

    plausible()->stats()
        ->for('localhost')
        ->metrics(Metric::Visitors)
        ->orWhere('visit:browser', 'is', 'Chrome')
        ->run();

    Http::assertSent(
        fn ($request) => $request->data()['filters'] === [['is', 'visit:browser', ['Chrome']]],
    );
});
