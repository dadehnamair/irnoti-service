<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlansSeeder extends Seeder
{
    /**
     * The three plans that used to be hard-coded in resources/views/landing.blade.php
     * (docs/starter.md §8). Editable from the Filament admin panel afterwards.
     * Safe to re-run: matched by slug.
     */
    public function run(): void
    {
        $plans = [
            [
                'slug' => 'basic',
                'name' => 'پایه',
                'description' => 'برای شروع کسب‌وکارهای کوچک و فروشگاه‌ها.',
                'badge_label' => 'برای شروع',
                'badge_style' => 'neutral',
                'price_monthly' => 990000,
                'price_yearly' => 9900000,
                'duration_days' => 30,
                'sms_count' => 1000,
                'lines_count' => 1,
                'users_count' => 1,
                'features' => ['۱٬۰۰۰ پیامک', '۱ خط اختصاصی', 'دسترسی به داشبورد', 'گزارش ارسال', 'API پایه'],
                'cta_label' => 'انتخاب پلن',
                'cta_style' => 'btn-secondary',
                'is_featured' => false,
                'is_active' => true,
                'sort' => 1,
            ],
            [
                'slug' => 'pro',
                'name' => 'حرفه‌ای',
                'description' => 'محبوب‌ترین پلن برای کسب‌وکارهای در حال رشد.',
                'badge_label' => 'پرفروش',
                'badge_style' => 'primary',
                'price_monthly' => 2490000,
                'price_yearly' => 24900000,
                'duration_days' => 30,
                'sms_count' => 10000,
                'lines_count' => 3,
                'users_count' => 5,
                'features' => ['۱۰٬۰۰۰ پیامک', '۳ خط اختصاصی', 'پیامک پترن', 'API کامل', 'پشتیبانی ویژه'],
                'cta_label' => 'انتخاب پلن',
                'cta_style' => 'btn-primary',
                'is_featured' => true,
                'is_active' => true,
                'sort' => 2,
            ],
            [
                'slug' => 'enterprise',
                'name' => 'سازمانی',
                'description' => 'برای سازمان‌ها و کسب‌وکارهای با حجم ارسال بالا.',
                'badge_label' => 'سفارشی',
                'badge_style' => 'dark',
                'price_monthly' => 7990000,
                'price_yearly' => 79900000,
                'duration_days' => 30,
                'sms_count' => 100000,
                'lines_count' => null,
                'users_count' => null,
                'features' => ['۱۰۰٬۰۰۰ پیامک', 'خطوط اختصاصی نامحدود', 'API اختصاصی', 'پشتیبانی ویژه ۲۴/۷', 'مدیریت تیمی'],
                'cta_label' => 'درخواست مشاوره',
                'cta_style' => 'btn-secondary',
                'is_featured' => false,
                'is_active' => true,
                'sort' => 3,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(['slug' => $plan['slug']], $plan);
        }
    }
}
