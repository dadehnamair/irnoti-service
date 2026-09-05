<?php

namespace Database\Seeders;

use App\Models\LineBundle;
use App\Models\LineGroup;
use App\Models\SmsLine;
use Illuminate\Database\Seeder;

/**
 * «باندل اختصاصی خط» sample products (docs/lines-landing.md) — a dedicated line
 * plus a chunk of SMS credit and a validity window, sold from the line landing
 * page. Runs after LineGroupsSeeder + SmsLinesSeeder. Idempotent — matched by slug.
 */
class LineBundlesSeeder extends Seeder
{
    public function run(): void
    {
        $bundles = [
            [
                'prefix' => '3000', 'digits' => 10, 'slug' => 'bundle-3000-starter',
                'title' => 'باندل شروع خط ۳۰۰۰', 'description' => 'خط اختصاصی ۳۰۰۰ ده رقمی به‌همراه ۵٬۰۰۰ پیامک هدیه.',
                'sms_credit' => 5000, 'validity_days' => 365, 'price' => 690000, 'compare_at_price' => 820000,
                'badge_label' => 'پرفروش', 'sort' => 1,
                'features' => ['خط ۱۰ رقمی ۳۰۰۰', '۵٬۰۰۰ پیامک اعتبار', 'اعتبار یک‌ساله', 'فعال‌سازی همان روز'],
            ],
            [
                'prefix' => '3000', 'digits' => 12, 'slug' => 'bundle-3000-eco',
                'title' => 'باندل اقتصادی خط ۳۰۰۰', 'description' => 'ارزان‌ترین راه شروع: خط ۳۰۰۰ دوازده رقمی + ۲٬۰۰۰ پیامک.',
                'sms_credit' => 2000, 'validity_days' => 180, 'price' => 320000, 'compare_at_price' => null,
                'badge_label' => null, 'sort' => 2,
                'features' => ['خط ۱۲ رقمی ۳۰۰۰', '۲٬۰۰۰ پیامک اعتبار', 'اعتبار شش‌ماهه'],
            ],
            [
                'prefix' => '1000', 'digits' => 9, 'slug' => 'bundle-1000-pro',
                'title' => 'باندل حرفه‌ای خط ۱۰۰۰', 'description' => 'خط ۱۰۰۰ نه رقمی به‌همراه ۱۵٬۰۰۰ پیامک برای کمپین‌های تبلیغاتی.',
                'sms_credit' => 15000, 'validity_days' => 365, 'price' => 1950000, 'compare_at_price' => 2300000,
                'badge_label' => 'ویژه', 'sort' => 1,
                'features' => ['خط ۹ رقمی ۱۰۰۰', '۱۵٬۰۰۰ پیامک اعتبار', 'اعتبار یک‌ساله', 'مناسب ارسال انبوه'],
            ],
            [
                'prefix' => '9000', 'digits' => 11, 'slug' => 'bundle-9000-basic',
                'title' => 'باندل پایه خط ۹۰۰۰', 'description' => 'خط ۹۰۰۰ رایتل + ۳٬۰۰۰ پیامک اطلاع‌رسانی.',
                'sms_credit' => 3000, 'validity_days' => 180, 'price' => 360000, 'compare_at_price' => null,
                'badge_label' => null, 'sort' => 1,
                'features' => ['خط ۱۱ رقمی ۹۰۰۰', '۳٬۰۰۰ پیامک اعتبار', 'اعتبار شش‌ماهه'],
            ],
        ];

        foreach ($bundles as $data) {
            $group = LineGroup::where('prefix', $data['prefix'])->first();

            if (! $group) {
                continue;
            }

            $line = SmsLine::where('prefix', $data['prefix'])->where('digits', $data['digits'])->first();

            LineBundle::updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'line_group_id' => $group->id,
                    'sms_line_id' => $line?->id,
                    'title' => $data['title'],
                    'description' => $data['description'],
                    'sms_credit' => $data['sms_credit'],
                    'validity_days' => $data['validity_days'],
                    'price' => $data['price'],
                    'compare_at_price' => $data['compare_at_price'],
                    'badge_label' => $data['badge_label'],
                    'features' => $data['features'],
                    'sort' => $data['sort'],
                    'is_active' => true,
                ],
            );
        }
    }
}
