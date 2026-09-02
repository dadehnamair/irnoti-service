<?php

namespace Database\Seeders;

use App\Models\MarketplaceApp;
use Illuminate\Database\Seeder;

/**
 * Seeds «بازارچه افزونه‌ها» (docs/starter.md §15). Idempotent (`updateOrCreate` by
 * slug); admin-owned bits (price, is_active) are only set on first insert so a
 * re-run does not stomp the panel. Structure fields always follow this file.
 */
class MarketplaceAppsSeeder extends Seeder
{
    public function run(): void
    {
        $apps = [
            [
                'slug' => 'irplus',
                'name' => 'ایرپلاس',
                'vendor' => 'ایرپلاس',
                'category' => 'integration',
                'tagline' => 'اتصال به سامانه ایرپلاس و دریافت خودکار لیست مسافران و گروه‌بندی‌ها',
                'description' => 'با افزودن این افزونه، کلید API آژانس خود در **ایرپلاس** را وارد می‌کنید و لیست مسافران و گروه‌بندی‌های آن‌ها به‌صورت یک دفترچه‌تلفن اختصاصی در سامانه ساخته می‌شود. سپس می‌توانید برای هر گروه پیامک ویژه ارسال کنید.',
                'handler' => 'irplus',
                'billing_type' => 'subscription',
                'price' => 150000,
                'billing_period' => 'monthly',
                'config_schema' => [
                    ['key' => 'api_key', 'label' => 'کلید API ایرپلاس', 'type' => 'text', 'required' => true, 'secret' => true, 'help' => 'از پنل ایرپلاس، بخش تنظیمات وب‌سرویس'],
                    ['key' => 'agency_code', 'label' => 'کد آژانس', 'type' => 'text', 'required' => true, 'secret' => false, 'help' => ''],
                    ['key' => 'base_url', 'label' => 'آدرس سرویس (اختیاری)', 'type' => 'text', 'required' => false, 'secret' => false, 'help' => 'در صورت خالی بودن، مقدار پیش‌فرض استفاده می‌شود'],
                ],
                'capabilities' => [],
                'is_featured' => true,
                'sort' => 10,
            ],
            [
                'slug' => 'business-card',
                'name' => 'کارت ویزیت',
                'vendor' => null,
                'category' => 'card',
                'tagline' => 'کارت ویزیت الکترونیکی کسب‌وکار داخل منوی پنل',
                'description' => 'با فعال‌سازی این افزونه، بخش «کارت ویزیت» در منوی پنل شما فعال می‌شود.',
                'handler' => 'feature_unlock',
                'billing_type' => 'one_time',
                'price' => 90000,
                'billing_period' => null,
                'config_schema' => [],
                'capabilities' => ['pro.business_card'],
                'is_featured' => false,
                'sort' => 20,
            ],
            [
                'slug' => 'sms-secretary',
                'name' => 'منشی پیامکی',
                'vendor' => null,
                'category' => 'messaging',
                'tagline' => 'پاسخ خودکار پیامکی به شماره‌های تماس‌گیرنده',
                'description' => 'با فعال‌سازی این افزونه، بخش «منشی» در منوی پنل شما فعال می‌شود.',
                'handler' => 'feature_unlock',
                'billing_type' => 'subscription',
                'price' => 60000,
                'billing_period' => 'monthly',
                'config_schema' => [],
                'capabilities' => ['pro.secretary'],
                'is_featured' => false,
                'sort' => 30,
            ],
            [
                'slug' => 'demo-free',
                'name' => 'افزونه نمونه (رایگان)',
                'vendor' => null,
                'category' => 'other',
                'tagline' => 'برای آزمایش فرایند نصب افزونه‌های رایگان',
                'description' => 'این افزونه صرفاً برای نمایش فرایند نصب رایگان است.',
                'handler' => 'feature_unlock',
                'billing_type' => 'free',
                'price' => 0,
                'billing_period' => null,
                'config_schema' => [],
                'capabilities' => [],
                'is_featured' => false,
                'sort' => 90,
            ],
        ];

        foreach ($apps as $row) {
            $app = MarketplaceApp::firstOrNew(['slug' => $row['slug']]);

            // Structure always follows the seeder.
            $app->fill([
                'name' => $row['name'],
                'vendor' => $row['vendor'],
                'category' => $row['category'],
                'tagline' => $row['tagline'],
                'description' => $row['description'],
                'handler' => $row['handler'],
                'billing_period' => $row['billing_period'],
                'config_schema' => $row['config_schema'],
                'capabilities' => $row['capabilities'],
                'sort' => $row['sort'],
            ]);

            // Admin-owned bits: seed once, then leave to the panel.
            if (! $app->exists) {
                $app->billing_type = $row['billing_type'];
                $app->price = $row['price'];
                $app->is_featured = $row['is_featured'];
                $app->is_active = true;
            }

            $app->save();
        }
    }
}
