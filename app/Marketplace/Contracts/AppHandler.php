<?php

namespace App\Marketplace\Contracts;

use App\Marketplace\AppRegistry;
use App\Models\MarketplaceInstallation;

/**
 * Behaviour of one «بازارچه» add-on (docs/starter.md §15). A concrete handler is
 * bound to a key in config('marketplace.handlers') and resolved through
 * {@see AppRegistry}. Mirrors the SMS provider layer: no
 * controller talks to a vendor service directly.
 */
interface AppHandler
{
    /** The key this handler is registered under (matches `marketplace_apps.handler`). */
    public function key(): string;

    /**
     * Validate + normalise the connection form values before they are stored on
     * the installation's encrypted `config`. Throw
     * Illuminate\Validation\ValidationException on bad input.
     *
     * @param  array<string, mixed>  $config
     * @return array<string, mixed> the cleaned config to persist
     */
    public function validateConfig(array $config): array;

    /** Provision the installation once it becomes active (create groups, grant features…). Idempotent. */
    public function onActivate(MarketplaceInstallation $installation): void;

    /** Tear the installation down when it is uninstalled / expires (revoke features…). Idempotent. */
    public function onDeactivate(MarketplaceInstallation $installation): void;

    /** Blade view for this app's own dashboard page, or null when it has none. */
    public function panelView(MarketplaceInstallation $installation): ?string;
}
