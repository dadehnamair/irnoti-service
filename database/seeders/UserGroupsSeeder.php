<?php

namespace Database\Seeders;

use App\Models\UserGroup;
use Illuminate\Database\Seeder;

/**
 * Seeds the default customer access group (docs/starter.md §15). New sign-ups
 * are dropped into it; assign features from the Filament admin panel.
 */
class UserGroupsSeeder extends Seeder
{
    public function run(): void
    {
        UserGroup::firstOrCreate(
            ['slug' => 'default'],
            [
                'name' => 'کاربر عادی',
                'description' => 'گروه پیش‌فرض کاربران تازه. امکانات این گروه را از پنل مدیریت مشخص کنید.',
                'is_default' => true,
                'sort' => 0,
            ],
        );
    }
}
