<?php

namespace Database\Seeders;

use App\Models\User;
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
        $default = UserGroup::firstOrCreate(
            ['slug' => 'default'],
            [
                'name' => 'کاربر عادی',
                'description' => 'گروه پیش‌فرض کاربران تازه. امکانات این گروه را از پنل مدیریت مشخص کنید.',
                'is_default' => true,
                'sort' => 0,
            ],
        );

        // Drop existing customers without a group into the default one.
        User::query()
            ->where('is_admin', false)
            ->whereNull('user_group_id')
            ->update(['user_group_id' => $default->id]);
    }
}
