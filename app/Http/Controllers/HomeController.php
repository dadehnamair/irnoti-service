<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\MarketplaceApp;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\SmsLine;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

class HomeController extends Controller
{
    /** @var array<int, array{q: string, a: string}> */
    private const FAQS = [
        ['q' => 'آیا irnoti برای شروع کسب‌وکارهای کوچک مناسب است؟', 'a' => 'بله، پلن‌های پایه و حرفه‌ای برای کسب‌وکارهای کوچک تا بزرگ طراحی شده‌اند و برای فروشگاه‌ها، خدمات و برندها مناسب هستند.'],
        ['q' => 'برای شروع چه چیزی لازم است؟', 'a' => 'فقط یک شماره موبایل. با ثبت‌نام و تأیید کد پیامکی وارد پنل می‌شوید؛ تکمیل هویت و مدارک را می‌توانید بعداً و به‌صورت مرحله‌به‌مرحله انجام دهید.'],
        ['q' => 'آیا امکان ارسال پیامک زمان‌بندی‌شده وجود دارد؟', 'a' => 'بله، از طریق پنل و API می‌توانید ارسال زمان‌بندی‌شده و کمپین‌های هدفمند داشته باشید.'],
        ['q' => 'بازارچه چیست؟', 'a' => 'بخشی از پنل که با آن می‌توانید قابلیت‌های تازه‌ای مثل اتصال به ایرپلاس، کارت ویزیت الکترونیکی یا منشی پیامکی را با چند کلیک به حساب خود اضافه کنید.'],
        ['q' => 'آیا API دارای مستندات فارسی است؟', 'a' => 'بله، مستندات API به‌صورت حرفه‌ای و ساده برای تیم‌های فنی آماده است و به‌راحتی در پروژه‌های مختلف قابل استفاده می‌باشد.'],
        ['q' => 'روش‌های پرداخت و تسویه چگونه است؟', 'a' => 'پرداخت آنلاین از طریق درگاه بانکی، شارژ کیف پول، ثبت فیش بانکی و صدور فاکتور رسمی برای کسب‌وکارها پشتیبانی می‌شود.'],
    ];

    /**
     * Landing page ("/"). All content-model queries degrade gracefully
     * (rescue) so the page renders on a fresh/empty DB — docs/starter.md §8.
     */
    public function index(): View
    {
        $plans = Plan::query()->active()->ordered()->get();

        $latestPosts = rescue(
            fn () => BlogPost::query()->published()->with('category')->limit(3)->get(),
            new Collection,
            false
        );

        $lines = rescue(
            fn () => SmsLine::query()->active()->orderBy('sort')->pluck('prefix')->unique()->values(),
            collect(['1000', '2000', '3000', '5000', '021', '9000']),
            false
        );

        $businessCardPrice = rescue(
            fn () => (int) Setting::get('business_card_standard_price', 0),
            600000,
            false
        );

        $marketApps = rescue(
            fn () => MarketplaceApp::query()->active()->ordered()->limit(4)->get(),
            new Collection,
            false
        );
        $marketEnabled = rescue(fn () => (bool) Setting::get('marketplace_enabled', true), true, false);

        return view('landing', [
            'plans' => $plans,
            'latestPosts' => $latestPosts,
            'lines' => $lines,
            'businessCardPrice' => $businessCardPrice,
            'marketApps' => $marketApps,
            'marketEnabled' => $marketEnabled,
            'faqs' => self::FAQS,
            'jsonLd' => $this->buildJsonLd($plans),
        ]);
    }

    /** JSON-LD graph: Organization + WebSite + one Product per plan + FAQPage. */
    private function buildJsonLd(Collection $plans): string
    {
        $brand = config('theme.brand');
        $seo = config('theme.seo');
        $url = rtrim($seo['url'], '/');

        $graph = [
            [
                '@type' => 'Organization',
                '@id' => $url.'/#organization',
                'name' => $brand,
                'url' => $url.'/',
                'logo' => $url.$seo['image'],
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
                '@id' => $url.'/#website',
                'name' => $brand,
                'url' => $url.'/',
                'inLanguage' => 'fa-IR',
                'publisher' => ['@id' => $url.'/#organization'],
            ],
        ];

        foreach ($plans as $plan) {
            $graph[] = [
                '@type' => 'Product',
                'name' => 'پلن '.$plan->name.' '.$brand,
                'description' => 'پنل پیامکی '.$brand.' — '.implode('، ', $plan->feature_list),
                'brand' => ['@type' => 'Brand', 'name' => $brand],
                'offers' => [
                    '@type' => 'Offer',
                    'price' => $plan->price_monthly * 10, // Toman -> Rial for ISO 4217
                    'priceCurrency' => 'IRR',
                    'availability' => 'https://schema.org/InStock',
                    'url' => $url.'/#pricing',
                    'priceValidUntil' => now()->addYear()->toDateString(),
                ],
            ];
        }

        $graph[] = [
            '@type' => 'FAQPage',
            'mainEntity' => collect(self::FAQS)->map(fn ($f) => [
                '@type' => 'Question',
                'name' => $f['q'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['a']],
            ])->all(),
        ];

        return json_encode(
            ['@context' => 'https://schema.org', '@graph' => $graph],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }
}
