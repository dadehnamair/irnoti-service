<?php

namespace App\Console\Commands;

use App\Models\MarketplaceInstallation;
use Illuminate\Console\Command;

/**
 * Flips past-due subscription add-ons to `expired` and runs the handler teardown
 * (revoke capability features) — docs/starter.md §15. Idempotent; safe to run
 * daily from the scheduler.
 */
class ExpireMarketplaceInstallations extends Command
{
    protected $signature = 'marketplace:expire';

    protected $description = 'Expire marketplace installations past their expires_at and revoke their capabilities';

    public function handle(): int
    {
        $due = MarketplaceInstallation::query()
            ->where('status', 'active')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->with('app')
            ->get();

        foreach ($due as $installation) {
            $installation->handler()->onDeactivate($installation);
            $installation->forceFill(['status' => 'expired'])->save();

            $this->line("Expired installation #{$installation->id} ({$installation->app?->slug}).");
        }

        $this->info("{$due->count()} installation(s) expired.");

        return self::SUCCESS;
    }
}
