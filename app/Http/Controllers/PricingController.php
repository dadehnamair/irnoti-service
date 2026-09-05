<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\SmsPackage;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

class PricingController extends Controller
{
    /** @var array<int, array{q: string, a: string}> */
    private const PRICING_FAQS = [
        ['q' => 'آیا امکان ارتقای پلن در میانه دوره وجود دارد؟', 'a' => 'بله، در هر زمان می‌توانید به پلن بالاتر ارتقا دهید و مابه‌التفاوت به‌صورت نسبی محاسبه می‌شود.'],
        ['q' => 'تفاوت صورت‌حساب ماهانه و سالانه چیست؟', 'a' => 'در پرداخت سالانه معادل حدود دو ماه تخفیف نسبت به پرداخت ماهانه دریافت می‌کنید.'],
        ['q' => 'آیا پیامک‌های مصرف‌نشده به ماه بعد منتقل می‌شوند؟', 'a' => 'سهمیه پیامک هر دوره در همان دوره معتبر است؛ برای حجم بالا پلن سازمانی گزینه بهتری است.'],
        ['q' => 'روش‌های پرداخت چگونه است؟', 'a' => 'پرداخت آنلاین از طریق درگاه بانکی و صدور فاکتور رسمی برای کسب‌وکارها امکان‌پذیر است.'],
    ];

    /**
     * Standalone pricing page ("/pricing") — the same plans shown on the
     * landing section, plus a full feature-comparison table. Plans are managed
     * from the Filament admin panel (docs/starter.md §8 / §40).
     */
    public function index(): View
    {
        $plans = Plan::query()->active()->ordered()->get();
        $brand = config('theme.brand');
        $canonical = route('pricing');

        return view('pricing', [
            'plans' => $plans,
            'allFeatures' => $plans->flatMap->feature_list->unique()->values(),
            'faqs' => self::PRICING_FAQS,
            'metaTitle' => 'تعرفه‌ها و پلن‌های '.$brand.' | پنل پیامک',
            'metaDescription' => 'مقایسه کامل پلن‌های پنل پیامک '.$brand
                .' — قیمت ماهانه و سالانه، تعداد پیامک، خطوط اختصاصی، کاربران و امکانات هر پلن.',
            'jsonLd' => $this->plansJsonLd($plans, $canonical, 'تعرفه‌ها'),
        ]);
    }

    /**
     * Public SMS credit-bundle catalogue ("/sms-packages"). Bundles are managed
     * from the Filament admin panel; buying one is done from the customer panel
     * (docs/starter.md §12).
     */
    public function packages(): View
    {
        $brand = config('theme.brand');
        $canonical = route('sms-packages');

        $packages = rescue(
            fn () => SmsPackage::query()->active()->ordered()->get(),
            new Collection,
            report: false,
        );

        return view('sms-packages', [
            'packages' => $packages,
            'metaTitle' => 'بسته‌های پیامکی '.$brand.' | خرید اعتبار پیامک',
            'metaDescription' => 'خرید بسته‌های پیامکی '.$brand
                .' — بسته‌های حجمی با قیمت مقطوع؛ اعتبار پیامکی خود را یک‌جا شارژ کنید.',
            'jsonLd' => $this->packagesJsonLd($packages, $canonical),
        ]);
    }

    /** JSON-LD graph: BreadcrumbList + one Product/Offer per plan. */
    private function plansJsonLd(Collection $plans, string $canonical, string $crumbLabel): string
    {
        $brand = config('theme.brand');
        $url = rtrim(config('theme.seo.url'), '/');

        $graph = [
            [
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => 'خانه', 'item' => $url.'/'],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => $crumbLabel, 'item' => $canonical],
                ],
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
                    'price' => $plan->price_monthly * 10,
                    'priceCurrency' => 'IRR',
                    'availability' => 'https://schema.org/InStock',
                    'url' => $canonical,
                    'priceValidUntil' => now()->addYear()->toDateString(),
                ],
            ];
        }

        return json_encode(
            ['@context' => 'https://schema.org', '@graph' => $graph],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }

    /** JSON-LD graph: BreadcrumbList + one Product/Offer per SMS package. */
    private function packagesJsonLd(Collection $packages, string $canonical): string
    {
        $brand = config('theme.brand');
        $url = rtrim(config('theme.seo.url'), '/');

        $graph = [
            [
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => 'خانه', 'item' => $url.'/'],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => 'بسته‌های پیامکی', 'item' => $canonical],
                ],
            ],
        ];

        foreach ($packages as $package) {
            $graph[] = [
                '@type' => 'Product',
                'name' => 'بسته پیامکی '.$package->name.' '.$brand,
                'description' => $package->description ?: (number_format($package->sms_count).' پیامک با قیمت مقطوع'),
                'brand' => ['@type' => 'Brand', 'name' => $brand],
                'offers' => [
                    '@type' => 'Offer',
                    'price' => $package->price * 10,
                    'priceCurrency' => 'IRR',
                    'availability' => 'https://schema.org/InStock',
                    'url' => $canonical,
                    'priceValidUntil' => now()->addYear()->toDateString(),
                ],
            ];
        }

        return json_encode(
            ['@context' => 'https://schema.org', '@graph' => $graph],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }
}
