<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@irnoti.com')],
            [
                'name' => env('ADMIN_NAME', 'مدیر irnoti'),
                'password' => Hash::make(env('ADMIN_PASSWORD', 'password')),
                'is_admin' => true,
                'status' => 'active',
                'email_verified_at' => now(),
            ],
        );

        $this->call([
            SettingsSeeder::class,
            FeaturesSeeder::class,
            UserGroupsSeeder::class,
            PlansSeeder::class,
            UssdPlansSeeder::class,
            SmsPackagesSeeder::class,
            MarketplaceAppsSeeder::class,
            SmsLinesSeeder::class,
            DomainsSeeder::class,
            BankAccountsSeeder::class,
            DocsSeeder::class,
            BlogSeeder::class,
            PagesSeeder::class,
            FaqsSeeder::class,
            SiteFeaturesSeeder::class,
            RepresentationTiersSeeder::class,
        ]);
    }
}
