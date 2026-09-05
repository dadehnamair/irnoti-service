<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Faq;
use App\Models\MarketplaceApp;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\SiteFeature;
use App\Models\SmsLine;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

class HomeController extends Controller
{
    /** Static fallback so the landing page still renders on a fresh/empty DB. */
    private const FEATURES_FALLBACK = [
        ['icon' => '✉️', 'title' => 'ارسال انبوه و گروهی', 'tagline' => 'ارسال فوری پیامک‌های فردی، گروهی و زمان‌بندی‌شده با کارایی بالا و صف اختصاصی.'],
        ['icon' => '📇', 'title' => 'دفترچه تلفن هوشمند', 'tagline' => 'مدیریت مخاطبین و گروه‌ها با همگام‌سازی دوطرفه با پنل شما و ورود دسته‌ای داده‌ها.'],
        ['icon' => '📨', 'title' => 'صندوق پیام‌ها', 'tagline' => 'مشاهده پیام‌های دریافتی و ارسالی، بایگانی و بروزرسانی لحظه‌ای وضعیت از سامانه.'],
        ['icon' => '👛', 'title' => 'کیف پول و فاکتور', 'tagline' => 'شارژ حساب، دفتر تراکنش شفاف، ثبت فیش بانکی و صدور فاکتور رسمی برای کسب‌وکارها.'],
        ['icon' => '🧩', 'title' => 'بازارچه', 'tagline' => 'افزودن قابلیت‌های تازه به پنل — اتصال به ایرپلاس، کارت ویزیت الکترونیکی و منشی پیامکی.'],
        ['icon' => '📞', 'title' => 'خطوط اختصاصی', 'tagline' => 'پیش‌شماره‌های ۱۰۰۰، ۲۰۰۰، ۳۰۰۰، ۵۰۰۰ و ۰۲۱ با انتخاب تعداد ارقام و خرید آنلاین.'],
        ['icon' => '🪪', 'title' => 'کارت ویزیت دیجیتال', 'tagline' => 'کارت ویزیت مجازی VBC یا کارت ویزیت الکترونیکی EBC با کد اختصاصی.'],
        ['icon' => '⚡', 'title' => 'وب‌سرویس و API', 'tagline' => 'اتصال سریع به اپلیکیشن‌ها، CRM و سیستم‌های فروش با مستندات فارسی و Webhook.'],
        ['icon' => '🛡️', 'title' => 'دسترسی و امنیت', 'tagline' => 'سطوح کاربری، کنترل دقیق دسترسی به بخش‌های پنل و ورود امن با کد یک‌بارمصرف.'],
    ];

    /**
     * Landing page ("/"). All content-model queries degrade gracefully
     * (rescue) so the page renders on a fresh/empty DB — docs/starter.md §8.
     */
    public function index(): View
    {
        $plans = Plan::query()->active()->ordered()->get();

        $latestPosts = rescue(
            fn() => BlogPost::query()->published()->with('category')->limit(3)->get(),
            new Collection,
            false
        );

        $lines = rescue(
            fn() => SmsLine::query()->active()->orderBy('sort')->pluck('prefix')->unique()->values(),
            collect(['1000', '2000', '3000', '5000', '021', '9000']),
            false
        );

        $businessCardPrice = rescue(
            fn() => (int) Setting::get('business_card_standard_price', 0),
            600000,
            false
        );

        $marketApps = rescue(
            fn() => MarketplaceApp::query()->active()->ordered()->limit(4)->get(),
            new Collection,
            false
        );
        $marketEnabled = rescue(fn() => (bool) Setting::get('marketplace_enabled', true), true, false);

        $faqs = rescue(fn() => Faq::query()->active()->ordered()->get(), new Collection, false);

        $siteFeatures = rescue(
            fn() => SiteFeature::query()->active()->ordered()->limit(9)->get(),
            collect(self::FEATURES_FALLBACK)->map(fn($f) => (object) $f),
            false
        );
        return view('landing', [
            'plans' => $plans,
            'latestPosts' => $latestPosts,
            'lines' => $lines,
            'businessCardPrice' => $businessCardPrice,
            'marketApps' => $marketApps,
            'marketEnabled' => $marketEnabled,
            'faqs' => $faqs,
            'siteFeatures' => $siteFeatures,
            'jsonLd' => $this->buildJsonLd($plans, $faqs),
        ]);
    }

    /** JSON-LD graph: Organization + WebSite + one Product per plan + FAQPage. */
    private function buildJsonLd(Collection $plans, Collection $faqs): string
    {
        $brand = config('theme.brand');
        $seo = config('theme.seo');
        $url = rtrim($seo['url'], '/');

        $graph = [
            [
                '@type' => 'Organization',
                '@id' => $url . '/#organization',
                'name' => $brand,
                'url' => $url . '/',
                'logo' => $url . $seo['image'],
                'description' => $seo['description'],
                'contactPoint' => [
                    '@type' => 'ContactPoint',
                    'contactType' => 'customer support',
                    'email' => config('theme.email'),
                    'telephone' => config('theme.phone'),
                    'areaServed' => 'IR',
                    'availableLanguage' => ['fa'],
                ],
            ],
            [
                '@type' => 'WebSite',
                '@id' => $url . '/#website',
                'name' => $brand,
                'url' => $url . '/',
                'inLanguage' => 'fa-IR',
                'publisher' => ['@id' => $url . '/#organization'],
            ],
        ];

        foreach ($plans as $plan) {
            $graph[] = [
                '@type' => 'Product',
                'name' => 'پلن ' . $plan->name . ' ' . $brand,
                'description' => 'پنل پیامکی ' . $brand . ' — ' . implode('، ', $plan->feature_list),
                'brand' => ['@type' => 'Brand', 'name' => $brand],
                'offers' => [
                    '@type' => 'Offer',
                    'price' => $plan->price_monthly * 10, // Toman -> Rial for ISO 4217
                    'priceCurrency' => 'IRR',
                    'availability' => 'https://schema.org/InStock',
                    'url' => $url . '/#pricing',
                    'priceValidUntil' => now()->addYear()->toDateString(),
                ],
            ];
        }

        $graph[] = [
            '@type' => 'FAQPage',
            'mainEntity' => $faqs->map(fn($f) => [
                '@type' => 'Question',
                'name' => $f->question,
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f->answer],
            ])->all(),
        ];

        return json_encode(
            ['@context' => 'https://schema.org', '@graph' => $graph],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }
}
