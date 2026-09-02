<?php

namespace App\Marketplace\Handlers;

use App\Marketplace\Contracts\AppHandler;
use App\Models\Feature;
use App\Models\MarketplaceInstallation;
use App\Models\UserFeatureOverride;

/**
 * The handler for internal-capability add-ons (کارت ویزیت، منشی پیامکی، …).
 * Installing one adds a `grant` {@see UserFeatureOverride} for every key in the
 * app's `capabilities`, so the existing dashboard sidebar (docs/starter.md §15)
 * lights those rows up; uninstalling removes them. No connection config.
 */
class FeatureUnlockHandler implements AppHandler
{
    public function key(): string
    {
        return 'feature_unlock';
    }

    public function validateConfig(array $config): array
    {
        return [];
    }

    public function onActivate(MarketplaceInstallation $installation): void
    {
        $featureIds = $this->featureIds($installation);

        foreach ($featureIds as $featureId) {
            UserFeatureOverride::updateOrCreate(
                ['user_id' => $installation->user_id, 'feature_id' => $featureId],
                ['mode' => 'grant'],
            );
        }
    }

    public function onDeactivate(MarketplaceInstallation $installation): void
    {
        $featureIds = $this->featureIds($installation);

        if ($featureIds === []) {
            return;
        }

        UserFeatureOverride::query()
            ->where('user_id', $installation->user_id)
            ->whereIn('feature_id', $featureIds)
            ->where('mode', 'grant')
            ->delete();
    }

    public function panelView(MarketplaceInstallation $installation): ?string
    {
        return null;
    }

    /** @return array<int, int> */
    private function featureIds(MarketplaceInstallation $installation): array
    {
        $keys = $installation->app?->capabilityKeys() ?? [];

        return $keys === []
            ? []
            : Feature::query()->whereIn('key', $keys)->pluck('id')->all();
    }
}
