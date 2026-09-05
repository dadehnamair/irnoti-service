<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class UssdPlansSeeder extends Seeder
{
    /**
     * Starter USSD code plans shown on the public "/ussd" page — sold and
     * activated through the same Plan/Subscription machinery as the SMS
     * subscription plans (plans.type = "ussd"). Editable from the Filament
     * admin panel afterwards. Safe to re-run: matched by slug.
     */
    public function run(): void
    {
        $plans = [
            [
                'slug' => 'ussd-mediated',
                'name' => 'کد دستوری با واسطه',
                'type' => 'ussd',
                'description' => 'کد دستوری اختصاصی با پیش‌شماره مشترک، مناسب راه‌اندازی سریع.',
                'badge_label' => 'شروع سریع',
                'badge_style' => 'neutral',
                'price_monthly' => 40000000,
                'price_yearly' => 400000000,
                'duration_days' => 365,
                'features' => ['منوهای نامحدود', 'پرداخت آفلاین تا ۲ میلیون ریال', 'سبد خرید و نظرسنجی', 'پشتیبانی راه‌اندازی'],
                'cta_label' => 'درخواست مشاوره',
                'cta_style' => 'btn-secondary',
                'is_featured' => false,
                'is_active' => true,
                'sort' => 1,
            ],
            [
                'slug' => 'ussd-direct',
                'name' => 'کد دستوری بی‌واسطه',
                'type' => 'ussd',
                'description' => 'کد دستوری اختصاصی بدون پیش‌شماره، برای برندهای بزرگ.',
                'badge_label' => 'حرفه‌ای',
                'badge_style' => 'primary',
                'price_monthly' => 150000000,
                'price_yearly' => 1500000000,
                'duration_days' => 365,
                'features' => ['منوهای نامحدود', 'پرداخت آنلاین تا سقف بالا', 'قرعه‌کشی و نوبت‌دهی', 'تأیید هویت مشتریان', 'پشتیبانی ویژه'],
                'cta_label' => 'درخواست مشاوره',
                'cta_style' => 'btn-primary',
                'is_featured' => true,
                'is_active' => true,
                'sort' => 2,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(['slug' => $plan['slug']], $plan);
        }
    }
}
