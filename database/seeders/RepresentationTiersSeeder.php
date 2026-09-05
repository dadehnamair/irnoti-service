<?php

namespace Database\Seeders;

use App\Models\RepresentationTier;
use Illuminate\Database\Seeder;

/** Default sales-representation tiers. Idempotent by slug. */
class RepresentationTiersSeeder extends Seeder
{
    public function run(): void
    {
        $tiers = [
            [
                'slug' => 'starter',
                'name' => 'نمایندگی آغازین',
                'tagline' => 'شروع همکاری بدون نیاز به سرمایه',
                'description' => 'مناسب افراد و کسب‌وکارهای کوچکی که می‌خواهند بدون سرمایه‌گذاری اولیه، فروش خدمات پیامکی را شروع کنند.',
                'investment_amount' => null,
                'commission_percent' => 10,
                'benefits' => ['بدون نیاز به سرمایه اولیه', 'پنل نمایندگی اختصاصی', 'پشتیبانی تلفنی همکاران'],
                'requirements' => 'داشتن شماره موبایل معتبر و حداقل ۱۸ سال سن.',
                'is_featured' => false,
                'sort' => 10,
            ],
            [
                'slug' => 'professional',
                'name' => 'نمایندگی حرفه‌ای',
                'tagline' => 'کمیسیون بالاتر برای همکاران فعال',
                'description' => 'برای آژانس‌های تبلیغاتی و فریلنسرهایی که به‌صورت مستمر مشتری معرفی می‌کنند.',
                'investment_amount' => 20000000,
                'commission_percent' => 20,
                'benefits' => ['کمیسیون بالاتر', 'قیمت نمایندگی ویژه خطوط اختصاصی', 'مدیر حساب اختصاصی', 'اولویت در کمپین‌های تبلیغاتی'],
                'requirements' => 'سابقه فعالیت تجاری یا آژانس تبلیغاتی.',
                'is_featured' => true,
                'sort' => 20,
            ],
            [
                'slug' => 'vip',
                'name' => 'نمایندگی VIP',
                'tagline' => 'همکاری استراتژیک برای شرکت‌ها',
                'description' => 'همکاری بلندمدت برای شرکت‌ها و مجموعه‌های بزرگ با حجم فروش بالا.',
                'investment_amount' => 50000000,
                'commission_percent' => 30,
                'benefits' => ['بالاترین سطح کمیسیون', 'قرارداد رسمی همکاری', 'دسترسی زودهنگام به امکانات جدید', 'برندسازی مشترک'],
                'requirements' => 'ثبت شرکت (حقوقی) و ارائه سابقه فروش.',
                'is_featured' => false,
                'sort' => 30,
            ],
        ];

        foreach ($tiers as $tier) {
            RepresentationTier::updateOrCreate(['slug' => $tier['slug']], $tier);
        }
    }
}
