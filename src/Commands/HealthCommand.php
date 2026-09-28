<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Commands;

use Illuminate\Console\Command;

/**
 * Reports the package's wiring status: whether the URL/key are configured, whether the
 * Plausible instance is reachable, and whether it accepts the key. `--site` verifies the
 * key against that site's Stats API (the default is `plausible.site`, else the Sites API).
 */
final class HealthCommand extends Command
{
    protected $signature = 'plausible:health
        {--site= : Verify the API key against this site\'s Stats API (defaults to PLAUSIBLE_SITE)}';

    protected $description = 'Check Plausible configuration and API connectivity';

    public function handle(): int
    {
        $site = $this->option('site');
        $health = plausible()->health(is_string($site) && $site !== '' ? $site : null);

        $this->line('Plausible health check');
        $this->line('  URL configured: '.($health->hasUrl ? 'yes' : 'no'));
        $this->line('  Key configured: '.($health->hasKey ? 'yes' : 'no'));
        $this->line('  Reachable:      '.($health->reachable ? 'yes' : 'no'));
        $this->line('  Key accepted:   '.($health->authorized ? 'yes' : 'no'));

        if ($health->status !== null) {
            $this->line('  HTTP status:    '.$health->status);
        }

        if ($health->ok()) {
            $this->info($health->message);

            return self::SUCCESS;
        }

        $this->error($health->message);

        return self::FAILURE;
    }
}
