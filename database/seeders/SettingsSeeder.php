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

            // خطوط اختصاصی و پلن‌ها — روش تکمیل خرید
            ['commerce', 'line_payment_online', '0', 'bool', 'خرید آنلاین خطوط (اتصال به درگاه پرداخت)'],
            ['commerce', 'plan_payment_online', '0', 'bool', 'خرید آنلاین پلن‌ها (اتصال به درگاه پرداخت)'],
            ['commerce', 'package_payment_online', '0', 'bool', 'خرید آنلاین بسته‌های پیامکی (اتصال به درگاه پرداخت)'],

            // بازارچه افزونه‌ها (docs/starter.md §15)
            ['commerce', 'marketplace_enabled', '1', 'bool', 'فعال بودن بازارچه افزونه‌ها'],
            ['commerce', 'marketplace_payment_online', '0', 'bool', 'خرید آنلاین افزونه‌های بازارچه (اتصال به درگاه پرداخت)'],

            // کیف پول و امور مالی (docs/starter.md §22 / §23)
            ['commerce', 'wallet_enabled', '1', 'bool', 'فعال بودن کیف پول و شارژ حساب'],
            ['commerce', 'wallet_min_topup', '10000', 'string', 'حداقل مبلغ شارژ کیف پول (تومان)'],
            ['commerce', 'invoice_number_prefix', 'INV', 'string', 'پیشوند شماره صورت‌حساب'],

            // ثبت فیش بانکی — روش پرداخت آفلاین (docs/starter.md §22)
            ['commerce', 'receipt_payment_enabled', '1', 'bool', 'فعال بودن ثبت فیش بانکی'],
            ['commerce', 'receipt_for_topup', '1', 'bool', 'ثبت فیش برای شارژ کیف پول'],
            ['commerce', 'receipt_for_plans', '1', 'bool', 'ثبت فیش برای خرید پلن'],
            ['commerce', 'receipt_for_lines', '1', 'bool', 'ثبت فیش برای خرید خط'],
            ['commerce', 'receipt_for_packages', '1', 'bool', 'ثبت فیش برای خرید بسته پیامکی'],
            ['commerce', 'receipt_for_marketplace', '1', 'bool', 'ثبت فیش برای خرید افزونه بازارچه'],
            ['commerce', 'receipt_for_invoices', '1', 'bool', 'ثبت فیش برای صورت‌حساب'],

            // ثبت‌نام و اطلاع‌رسانی پیامکی (docs/starter.md §26 / §44)
            ['account', 'registration_enabled', '1', 'bool', 'فعال بودن ثبت‌نام کاربران'],
            ['account', 'sms_notifications_enabled', '1', 'bool', 'اطلاع‌رسانی پیامکی رویدادها'],
            ['account', 'sms_provider_label', config('sms.label', 'سامانه پیامک'), 'string', 'نام نمایشی سامانه پیامک (برای مشتری)'],
            ['account', 'sms_delivery_sync_enabled', '1', 'bool', 'پیگیری خودکار وضعیت تحویل پیامک‌های ارسالی'],
            ['account', 'admin_mobile', env('ADMIN_MOBILE', ''), 'string', 'موبایل مدیر برای اطلاع‌رسانی'],
            ['account', 'require_admin_approval', '1', 'bool', 'نیاز به تأیید مدیر برای فعال‌سازی امکانات پنل'],

            // دفترچه تلفن (docs/starter.md §17)
            ['account', 'phonebook_enabled', '1', 'bool', 'فعال بودن دفترچه تلفن و ارسال گروهی'],

            // صفحات حقوقی فوتر (docs/starter.md §67) — بدنه مارک‌داون، قابل ویرایش از پنل
            ['legal', 'legal_terms_body', "## قوانین و مقررات\n\nاین متن نمونه است و باید از پنل مدیریت با متن نهایی قوانین و مقررات استفاده از سرویس جایگزین شود.\n\nبا استفاده از خدمات این وبگاه، کاربر می‌پذیرد که مفاد این توافق‌نامه را مطالعه کرده و با آن موافق است.", 'text', 'متن صفحه «قوانین و مقررات»'],
            ['legal', 'legal_privacy_body', "## حریم خصوصی\n\nاین متن نمونه است و باید از پنل مدیریت با سیاست حفظ حریم خصوصی نهایی جایگزین شود.\n\nاطلاعات کاربران تنها برای ارائه خدمات استفاده شده و بدون رضایت کاربر در اختیار اشخاص ثالث قرار نمی‌گیرد.", 'text', 'متن صفحه «حریم خصوصی»'],

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
