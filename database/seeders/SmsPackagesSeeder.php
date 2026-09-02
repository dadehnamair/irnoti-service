<?php

namespace Database\Seeders;

use App\Models\SmsPackage;
use Illuminate\Database\Seeder;

/**
 * Sample SMS credit bundles (docs/starter.md §12). Idempotent — matched by slug,
 * safe to re-run. Prices are illustrative; the admin edits them from Filament.
 */
class SmsPackagesSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['بسته ۵٬۰۰۰ تایی', 'sms-5000', 5000, 400000, 450000, null, false],
            ['بسته ۱۰٬۰۰۰ تایی', 'sms-10000', 10000, 750000, 900000, 'پرفروش', true],
            ['بسته ۵۰٬۰۰۰ تایی', 'sms-50000', 50000, 3500000, 4500000, null, false],
            ['بسته ۱۰۰٬۰۰۰ تایی', 'sms-100000', 100000, 6500000, 9000000, 'به‌صرفه', false],
        ];

        foreach ($rows as $i => [$name, $slug, $count, $price, $compareAt, $badge, $featured]) {
            SmsPackage::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'sms_count' => $count,
                    'price' => $price,
                    'compare_at_price' => $compareAt,
                    'badge_label' => $badge,
                    'is_featured' => $featured,
                    'is_active' => true,
                    'sort' => $i,
                ],
            );
        }
    }
}
