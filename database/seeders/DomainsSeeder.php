<?php

namespace Database\Seeders;

use App\Models\Domain;
use Illuminate\Database\Seeder;

class DomainsSeeder extends Seeder
{
    /**
     * Vanity-link domains for digital business cards. irnoti.com is the
     * default (standard-tier cards, no custom-code pricing); 11v.ir and
     * 7db.ir are seeded with sample code_price_tiers as an editable starting
     * point (docs/starter.md — کارت ویزیت دیجیتال). Safe to re-run: matched
     * by host.
     */
    public function run(): void
    {
        $domains = [
            [
                'host' => 'irnoti.com',
                'label' => 'دامنه اصلی',
                'is_active' => true,
                'is_default' => true,
                'code_price_tiers' => null,
                'sort' => 1,
            ],
            [
                'host' => '11v.ir',
                'label' => 'دامنه ویژه ۱۱وی',
                'is_active' => true,
                'is_default' => false,
                'code_price_tiers' => [
                    ['label' => 'کد تک‌رقمی', 'type' => 'numeric', 'min_length' => 1, 'max_length' => 1, 'price' => 25000000],
                    ['label' => 'کد پنج‌رقمی', 'type' => 'numeric', 'min_length' => 5, 'max_length' => 5, 'price' => 10000000],
                    ['label' => 'کد ۶ تا ۱۱ رقمی', 'type' => 'numeric', 'min_length' => 6, 'max_length' => 11, 'price' => 6500000],
                    ['label' => 'کد دو‌حرفی', 'type' => 'alpha', 'min_length' => 2, 'max_length' => 2, 'price' => 80000000],
                ],
                'sort' => 2,
            ],
            [
                'host' => '7db.ir',
                'label' => 'دامنه ویژه ۷دیبی',
                'is_active' => true,
                'is_default' => false,
                'code_price_tiers' => [
                    ['label' => 'کد تک‌رقمی', 'type' => 'numeric', 'min_length' => 1, 'max_length' => 1, 'price' => 20000000],
                    ['label' => 'کد پنج‌رقمی', 'type' => 'numeric', 'min_length' => 5, 'max_length' => 5, 'price' => 8000000],
                    ['label' => 'کد ۶ تا ۱۱ رقمی', 'type' => 'numeric', 'min_length' => 6, 'max_length' => 11, 'price' => 5000000],
                    ['label' => 'کد دو‌حرفی', 'type' => 'alpha', 'min_length' => 2, 'max_length' => 2, 'price' => 60000000],
                ],
                'sort' => 3,
            ],
        ];

        foreach ($domains as $domain) {
            Domain::updateOrCreate(['host' => $domain['host']], $domain);
        }
    }
}
