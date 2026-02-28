<?php

namespace App\Console\Commands;

use App\Services\SubscriptionService;
use Illuminate\Console\Command;

/**
 * Daily command to transition expired subscriptions through their lifecycle.
 *
 * Schedule: `$schedule->command('subscriptions:process-expiries')->daily();`
 */
class ProcessSubscriptionExpiries extends Command
{
    protected $signature = 'subscriptions:process-expiries';

    protected $description = 'Process subscription expiry transitions (Active → Grace → Expired)';

    public function handle(SubscriptionService $service): int
    {
        $this->info('Processing subscription expiries...');

        $stats = $service->processExpiries();

        $this->info("Moved to grace: {$stats['to_grace']}");
        $this->info("Moved to expired: {$stats['to_expired']}");

        return self::SUCCESS;
    }
}
