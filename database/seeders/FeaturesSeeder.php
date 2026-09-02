<?php

namespace Database\Seeders;

use App\Models\Feature;
use App\Support\FeatureCatalog;
use Illuminate\Database\Seeder;

/**
 * Writes the customer dashboard sidebar catalogue (docs/starter.md §15) into the
 * `features` table. The catalogue is authoritative for structure: menu group,
 * order, route and «system» flag are re-synced on every run and stale rows are
 * pruned. The admin-owned bits are preserved — `label` and, for «بزودی» items,
 * the `is_active` toggle.
 */
class FeaturesSeeder extends Seeder
{
    public function run(): void
    {
        foreach (FeatureCatalog::all() as $row) {
            $feature = Feature::firstOrNew(['key' => $row['key']]);

            $feature->fill([
                'group_key' => $row['group_key'],
                'group_label' => $row['group_label'],
                'route' => $row['route'],
                'url' => $row['url'],
                'is_system' => $row['system'],
                'sort' => $row['sort'],
            ]);

            // Structural fields above always follow the catalogue. Label and the
            // «بزودی» switch are set once, then left to the admin.
            if (! $feature->exists) {
                $feature->label = $row['label'];
                $feature->is_active = $row['system']; // system pages ship enabled
            } elseif ($row['system']) {
                $feature->is_active = true; // a built-in page is never «بزودی»
            }

            $feature->save();
        }

        // The catalogue is the whole menu — drop anything it no longer defines.
        Feature::query()->whereNotIn('key', FeatureCatalog::keys())->delete();
    }
}
