<?php

namespace Database\Seeders;

use App\Models\Feature;
use App\Support\FeatureCatalog;
use Illuminate\Database\Seeder;

/**
 * Writes the customer dashboard mega-menu catalogue (docs/starter.md §15) into
 * the `features` table. Idempotent and matched by `key` — re-running keeps any
 * `is_active` toggles and label/route tweaks the admin has made.
 */
class FeaturesSeeder extends Seeder
{
    public function run(): void
    {
        foreach (FeatureCatalog::all() as $row) {
            Feature::firstOrCreate(
                ['key' => $row['key']],
                [
                    'group_key' => $row['group_key'],
                    'group_label' => $row['group_label'],
                    'label' => $row['label'],
                    'route' => $row['route'],
                    'sort' => $row['sort'],
                    'is_active' => false, // ships disabled — «بزودی فعال می‌شوند»
                ],
            );
        }
    }
}
