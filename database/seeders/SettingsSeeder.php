<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * The public-site "base info" (docs/starter.md §67). Mirrors the defaults in
     * config/theme.php; once seeded these rows win (see AppServiceProvider).
     * Safe to re-run: matched by key, existing values are kept.
     */
    public function run(): void
    {
        $rows = [
            // group, key, value, type, label
            ['general', 'brand', config('theme.brand'), 'string', 'نام سایت'],
            ['general', 'tagline', config('theme.tagline'), 'text', 'شعار'],

            ['theme', 'primary', config('theme.primary'), 'color', 'رنگ اصلی برند'],
            ['theme', 'accent', config('theme.accent'), 'color', 'رنگ مکمل (گرادیان)'],
            ['theme', 'secondary', config('theme.secondary'), 'color', 'رنگ ثانویه (موفقیت/آنلاین)'],

            ['contact', 'email', config('theme.email'), 'string', 'ایمیل'],
            ['contact', 'phone', config('theme.phone'), 'string', 'شماره تماس (tel:)'],
            ['contact', 'phone_display', config('theme.phone_display'), 'string', 'شماره تماس (نمایشی)'],
            ['contact', 'address', config('theme.address', ''), 'text', 'آدرس'],

            ['seo', 'seo_title', config('theme.seo.title'), 'text', 'عنوان سئو'],
            ['seo', 'seo_description', config('theme.seo.description'), 'text', 'توضیحات سئو'],
            ['seo', 'seo_keywords', config('theme.seo.keywords'), 'text', 'کلمات کلیدی'],
            ['seo', 'seo_image', config('theme.seo.image'), 'string', 'تصویر شبکه‌های اجتماعی'],

            // خطوط اختصاصی — روش تکمیل خرید
            ['commerce', 'line_payment_online', '0', 'bool', 'خرید آنلاین خطوط (اتصال به درگاه پرداخت)'],

            ['social', 'social_instagram', '', 'url', 'اینستاگرام'],
            ['social', 'social_telegram', '', 'url', 'تلگرام'],
            ['social', 'social_linkedin', '', 'url', 'لینکدین'],
            ['social', 'social_x', '', 'url', 'X (توییتر)'],
        ];

        foreach ($rows as $i => [$group, $key, $value, $type, $label]) {
            Setting::firstOrCreate(
                ['key' => $key],
                ['value' => $value, 'type' => $type, 'group' => $group, 'label' => $label, 'sort' => $i],
            );
        }
    }
}
