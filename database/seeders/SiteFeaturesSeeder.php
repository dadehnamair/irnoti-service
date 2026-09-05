<?php

namespace Database\Seeders;

use App\Models\SiteFeature;
use Illuminate\Database\Seeder;

/**
 * Default marketing feature-card catalogue (migrated from the old hardcoded
 * landing #features grid, plus the subsystems it was missing — messenger and
 * sales representation). Idempotent by title.
 */
class SiteFeaturesSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['icon' => '✉️', 'title' => 'ارسال انبوه و گروهی', 'tagline' => 'ارسال فوری، گروهی و زمان‌بندی‌شده', 'description' => 'ارسال فوری پیامک‌های فردی، گروهی و زمان‌بندی‌شده با کارایی بالا و صف اختصاصی برای هر حساب.', 'category' => 'sms', 'href' => null],
            ['icon' => '📇', 'title' => 'دفترچه تلفن هوشمند', 'tagline' => 'مدیریت مخاطبین با همگام‌سازی دوطرفه', 'description' => 'مدیریت مخاطبین و گروه‌ها با همگام‌سازی دوطرفه با پنل شما، ورود دسته‌ای داده‌ها و ارسال پیامک گروهی مستقیم از دفترچه.', 'category' => 'contacts', 'href' => null],
            ['icon' => '📨', 'title' => 'صندوق پیام‌ها', 'tagline' => 'آرشیو کامل دریافتی و ارسالی', 'description' => 'مشاهده پیام‌های دریافتی و ارسالی، بایگانی و بروزرسانی لحظه‌ای وضعیت مستقیم از سامانه ارسال.', 'category' => 'sms', 'href' => null],
            ['icon' => '🧠', 'title' => 'پیامک پترن', 'tagline' => 'الگوهای تراکنشی آماده', 'description' => 'ساخت الگوهای پیامکی استاندارد و استفاده مجدد برای ارسال‌های تراکنشی، کد تأیید و اطلاع‌رسانی‌های تکرارشونده.', 'category' => 'sms', 'href' => null],
            ['icon' => '👛', 'title' => 'کیف پول و فاکتور', 'tagline' => 'شارژ، تراکنش شفاف، فاکتور رسمی', 'description' => 'شارژ حساب، دفتر تراکنش شفاف (لجر تغییرناپذیر)، ثبت فیش بانکی و صدور فاکتور رسمی برای کسب‌وکارها.', 'category' => 'finance', 'href' => null],
            ['icon' => '🧩', 'title' => 'بازارچه افزونه‌ها', 'tagline' => 'قابلیت‌های تازه با چند کلیک', 'description' => 'افزودن قابلیت‌های تازه به پنل — اتصال به ایرپلاس، کارت ویزیت الکترونیکی، منشی پیامکی و موارد بیشتر.', 'category' => 'marketplace', 'href' => '/marketplace', 'is_featured' => true],
            ['icon' => '📞', 'title' => 'خطوط اختصاصی', 'tagline' => 'پیش‌شماره‌های ۱۰۰۰ تا ۰۲۱', 'description' => 'پیش‌شماره‌های ۱۰۰۰، ۲۰۰۰، ۳۰۰۰، ۵۰۰۰ و ۰۲۱ با انتخاب تعداد ارقام، رند بودن و خرید آنلاین.', 'category' => 'lines', 'href' => '/lines'],
            ['icon' => '🪪', 'title' => 'کارت ویزیت دیجیتال', 'tagline' => 'VBC / EBC با کد اختصاصی', 'description' => 'کارت ویزیت مجازی (VBC) یا کارت ویزیت الکترونیکی (EBC) با کد اختصاصی و لینک اشتراک‌گذاری آسان روی دامنه‌ی کوتاه.', 'category' => 'cards', 'href' => null, 'is_featured' => true, 'badge' => 'جدید'],
            ['icon' => '⚡', 'title' => 'وب‌سرویس و API', 'tagline' => 'مستندات فارسی، اتصال سریع', 'description' => 'اتصال سریع به اپلیکیشن‌ها، CRM و سیستم‌های فروش با مستندات فارسی، نمونه‌کد آماده و Webhook.', 'category' => 'developers', 'href' => '/developers'],
            ['icon' => '🛡️', 'title' => 'دسترسی و امنیت', 'tagline' => 'کنترل دقیق دسترسی + OTP', 'description' => 'سطوح کاربری، کنترل دقیق دسترسی به بخش‌های پنل برای زیرمجموعه‌ها و ورود امن با کد یک‌بارمصرف.', 'category' => 'security', 'href' => null],
            ['icon' => '💬', 'title' => 'ارسال به پیام‌رسان‌ها', 'tagline' => 'بله، ایتا و واتساپ', 'description' => 'ارسال انبوه پیام به بله، ایتا و واتساپ از همان دفترچه تلفن — با بازگشت خودکار هزینه‌ی بخش ناموفق.', 'category' => 'messenger', 'href' => null, 'is_featured' => true, 'badge' => 'جدید'],
            ['icon' => '🤝', 'title' => 'نمایندگی فروش', 'tagline' => 'همکاری در فروش با تعرفه‌های متنوع', 'description' => 'با تعریف نمایندگی فروش، در کنار ما درآمد کسب کنید — تعرفه‌ها و شرایط همکاری را ببینید و همین حالا درخواست دهید.', 'category' => 'sales', 'href' => '/representation', 'badge' => 'جدید'],
        ];

        foreach ($rows as $i => $row) {
            SiteFeature::updateOrCreate(
                ['title' => $row['title']],
                array_merge([
                    'icon' => null,
                    'tagline' => null,
                    'description' => null,
                    'category' => 'other',
                    'badge' => null,
                    'href' => null,
                    'is_featured' => false,
                    'is_active' => true,
                    'sort' => $i * 10,
                ], $row),
            );
        }
    }
}
