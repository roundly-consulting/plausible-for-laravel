<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Commands;

use Illuminate\Console\Command;

/**
 * Reports the package's wiring status: whether the URL/key are configured and
 * whether the Plausible API is reachable with the given key.
 */
final class HealthCommand extends Command
{
    protected $signature = 'plausible:health';

    protected $description = 'Check Plausible configuration and API connectivity';

    public function handle(): int
    {
        $health = plausible()->health();

        $this->line('Plausible health check');
        $this->line('  URL configured: '.($health->hasUrl ? 'yes' : 'no'));
        $this->line('  Key configured: '.($health->hasKey ? 'yes' : 'no'));
        $this->line('  Reachable:      '.($health->reachable ? 'yes' : 'no'));

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
