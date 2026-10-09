<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Repositories;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RoundlyConsulting\Plausible\Concerns\InteractsWithRateLimits;
use RoundlyConsulting\Plausible\Exceptions\MissingConfiguration;
use RoundlyConsulting\Plausible\Support\PlausibleConfig;
use Throwable;

abstract class Repository
{
    use InteractsWithRateLimits;

    /**
     * The authenticated client for the Stats and Sites APIs: the base client plus the
     * API key as a Bearer token. Throttled surfaces pass `throw: false` so the limiter
     * callback can return the raw 429 for hcrl to read `Retry-After`;
     * `Response::throw()` is then applied after the limiter (see the concern).
     */
    protected function request(bool $throw = true): PendingRequest
    {
        $client = $this->client($throw);
        $key = PlausibleConfig::present(config('plausible.key'));

        if ($key === null) {
            throw MissingConfiguration::key();
        }

        return $client->withToken($key);
    }

    /**
     * The unauthenticated base client: instance URL, timeout and retries. The Events API
     * builds on this directly — Plausible's `/api/event` takes no API key.
     *
     * Retries never throw: the last failed response is returned, so the throttled surfaces
     * hand it to the rate limiter (which reads a 429's `Retry-After`) before raising it.
     */
    protected function client(bool $throw = true): PendingRequest
    {
        $url = PlausibleConfig::present(config('plausible.url'));

        if ($url === null) {
            throw MissingConfiguration::url();
        }

        $request = Http::baseUrl($url)->timeout($this->timeout());

        if ($throw) {
            $request->throw();
        }

        if ($this->retryTimes() > 0) {
            $request->retry(
                $this->retryTimes(),
                $this->retrySleep(),
                fn (Throwable $exception): bool => $this->shouldRetry($exception),
                throw: false,
            );
        }

        return $request;
    }

    /**
     * Retry only what a second try can fix: a failed connection or a 5xx. A 4xx — a 429
     * included, which the rate limiter must see — is final.
     */
    protected function shouldRetry(Throwable $exception): bool
    {
        if ($exception instanceof ConnectionException) {
            return true;
        }

        return $exception instanceof RequestException && $exception->response->serverError();
    }

    /**
     * Walk a cursor-paginated Sites API list endpoint: follow `meta.after` until Plausible
     * answers null (or repeats a cursor), collecting each page's `$key` rows. Every page
     * goes through the `sites` rate limiter.
     *
     * @param  array<string, string>  $query
     * @return list<array<array-key, mixed>>
     */
    protected function paginate(string $path, string $key, ?string $site, array $query = []): array
    {
        $rows = [];
        $seen = [];
        $after = null;

        do {
            $params = $after === null ? $query : [...$query, 'after' => $after];

            $response = $this->throttled(
                'sites',
                $site,
                fn (): Response => $this->request(throw: false)->get($path, $params),
            );

            $page = $response->json($key);

            foreach (is_array($page) ? $page : [] as $row) {
                if (is_array($row)) {
                    $rows[] = $row;
                }
            }

            $seen[] = $after;
            $next = $response->json('meta.after');
            $after = is_string($next) && $next !== '' && ! in_array($next, $seen, true) ? $next : null;
        } while ($after !== null);

        return $rows;
    }

    /**
     * Encode a value for use as ONE URL path segment. Plausible site ids may contain `/`
     * (sub-folder sites), and a tenant-supplied id could carry `?`, `#` or a dot-segment —
     * raw, any of those would re-route the request to another endpoint.
     */
    protected static function segment(int|string $value): string
    {
        $encoded = rawurlencode((string) $value);

        return $encoded === '.' || $encoded === '..' ? str_replace('.', '%2E', $encoded) : $encoded;
    }

    /**
     * The configured default site (domain), or null when none is set.
     */
    public function defaultSite(): ?string
    {
        return PlausibleConfig::present(config('plausible.site'));
    }

    protected function timeout(): int
    {
        return PlausibleConfig::timeout();
    }

    protected function retryTimes(): int
    {
        return PlausibleConfig::retryTimes();
    }

    protected function retrySleep(): int
    {
        return PlausibleConfig::retrySleep();
    }
}
