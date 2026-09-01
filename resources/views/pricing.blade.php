@php
    $brand = config('theme.brand');
    $seo = config('theme.seo');
    $primary = config('theme.primary');
    $email = config('theme.email');
    $url = rtrim($seo['url'], '/');
    $canonical = route('pricing');

    $metaTitle = 'تعرفه‌ها و پلن‌های ' . $brand . ' | پنل پیامک';
    $metaDescription = 'مقایسه کامل پلن‌های پنل پیامک ' . $brand
        . ' — قیمت ماهانه و سالانه، تعداد پیامک، خطوط اختصاصی، کاربران و امکانات هر پلن.';

    // Union of every feature across the active plans → comparison matrix rows.
    $allFeatures = $plans->flatMap->feature_list->unique()->values();

    $faqs = [
        ['q' => 'آیا امکان ارتقای پلن در میانه دوره وجود دارد؟', 'a' => 'بله، در هر زمان می‌توانید به پلن بالاتر ارتقا دهید و مابه‌التفاوت به‌صورت نسبی محاسبه می‌شود.'],
        ['q' => 'تفاوت صورت‌حساب ماهانه و سالانه چیست؟', 'a' => 'در پرداخت سالانه معادل حدود دو ماه تخفیف نسبت به پرداخت ماهانه دریافت می‌کنید.'],
        ['q' => 'آیا پیامک‌های مصرف‌نشده به ماه بعد منتقل می‌شوند؟', 'a' => 'سهمیه پیامک هر دوره در همان دوره معتبر است؛ برای حجم بالا پلن سازمانی گزینه بهتری است.'],
        ['q' => 'روش‌های پرداخت چگونه است؟', 'a' => 'پرداخت آنلاین از طریق درگاه بانکی و صدور فاکتور رسمی برای کسب‌وکارها امکان‌پذیر است.'],
    ];

    $graph = [
        [
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'خانه', 'item' => $url . '/'],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'تعرفه‌ها', 'item' => $canonical],
            ],
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
                'price' => $plan->price_monthly * 10,
                'priceCurrency' => 'IRR',
                'availability' => 'https://schema.org/InStock',
                'url' => $canonical,
                'priceValidUntil' => now()->addYear()->toDateString(),
            ],
        ];
    }

    $jsonLd = json_encode(
        ['@context' => 'https://schema.org', '@graph' => $graph],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
@endphp
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1" />
    <meta name="theme-color" content="{{ $primary }}" />
    <meta name="author" content="{{ $brand }}" />
    <meta name="description" content="{{ $metaDescription }}" />

    <meta property="og:type" content="website" />
    <meta property="og:site_name" content="{{ $brand }}" />
    <meta property="og:locale" content="{{ $seo['locale'] }}" />
    <meta property="og:title" content="{{ $metaTitle }}" />
    <meta property="og:description" content="{{ $metaDescription }}" />
    <meta property="og:url" content="{{ $canonical }}" />
    <meta property="og:image" content="{{ $url }}{{ $seo['image'] }}" />
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="{{ $metaTitle }}" />
    <meta name="twitter:description" content="{{ $metaDescription }}" />
    <meta name="twitter:image" content="{{ $url }}{{ $seo['image'] }}" />

    <link rel="canonical" href="{{ $canonical }}" />
    <link rel="icon" href="/logo/favicon.png" type="image/png" />

    <title>{{ $metaTitle }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800;900&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ route('theme.css') }}" />

    <script type="application/ld+json">
        @php echo $jsonLd;
        @endphp
    </script>
</head>

<body>
    <div class="page-shell">
        @include('partials.site-header')

        <main id="top">
            <section class="section pricing-hero">
                <div class="container">
                    <nav class="blog-breadcrumb" aria-label="مسیر">
                        <a href="{{ route('home') }}">خانه</a>
                        <span aria-hidden="true">/</span>
                        <span>تعرفه‌ها</span>
                    </nav>

                    <div class="section-heading center">
                        <span class="kicker">تعرفه‌ها</span>
                        <h1>پلن مناسب کسب‌وکار شما را انتخاب کنید</h1>
                        <p>همه پلن‌ها شامل داشبورد کامل، گزارش لحظه‌ای ارسال و دسترسی به API هستند. هر زمان می‌توانید ارتقا دهید.</p>
                    </div>

                    <div class="pricing-toggle" role="group" aria-label="تغییر دوره قیمت">
                        <button class="toggle-btn active" type="button" data-period="monthly" aria-pressed="true">ماهانه</button>
                        <button class="toggle-btn" type="button" data-period="yearly" aria-pressed="false">سالانه</button>
                    </div>

                    <div class="pricing-grid">
                        @forelse ($plans as $plan)
                        <article class="pricing-card @if ($plan->is_featured) featured @endif"
                            @if ($plan->color) style="--card-accent: {{ $plan->color }}" @endif>
                            <div class="pricing-header">
                                <h3>{{ $plan->name }}</h3>
                                @if ($plan->badge_label)
                                <span class="pill {{ $plan->badge_style }}">{{ $plan->badge_label }}</span>
                                @endif
                            </div>
                            @if ($plan->description)
                            <p class="plan-desc">{{ $plan->description }}</p>
                            @endif
                            <div class="price-wrap">
                                <span class="currency">تومان</span>
                                @if ($plan->compare_at_monthly)
                                <span class="price-compare"
                                    data-monthly="{{ $plan->compare_at_monthly }}"
                                    data-yearly="{{ $plan->compare_at_yearly ?: $plan->compare_at_monthly * 10 }}">{{ number_format($plan->compare_at_monthly) }}</span>
                                @endif
                                <span class="price"
                                    data-monthly="{{ $plan->price_monthly }}"
                                    data-yearly="{{ $plan->price_yearly }}">{{ number_format($plan->price_monthly) }}</span>
                                <span class="period">/ماه</span>
                            </div>
                            <ul>
                                @foreach ($plan->feature_list as $feature)
                                <li>{{ $feature }}</li>
                                @endforeach
                            </ul>
                            <a href="{{ $plan->cta_url ?: route('home') . '/#cta' }}" class="btn {{ $plan->cta_style }} full">{{ $plan->cta_label }}</a>
                        </article>
                        @empty
                        <p class="pricing-empty">به‌زودی پلن‌های تعرفه در این بخش نمایش داده می‌شوند.</p>
                        @endforelse
                    </div>
                </div>
            </section>

            @if ($plans->isNotEmpty())
            <section class="section alt-bg" aria-labelledby="compare-title">
                <div class="container">
                    <div class="section-heading center">
                        <span class="kicker">مقایسه</span>
                        <h2 id="compare-title">مقایسه کامل پلن‌ها</h2>
                    </div>

                    <div class="compare-wrap">
                        <table class="compare-table">
                            <thead>
                                <tr>
                                    <th scope="col">ویژگی</th>
                                    @foreach ($plans as $plan)
                                    <th scope="col" @if ($plan->is_featured) class="is-featured" @endif>{{ $plan->name }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <th scope="row">قیمت ماهانه</th>
                                    @foreach ($plans as $plan)
                                    <td>{{ number_format($plan->price_monthly) }} تومان</td>
                                    @endforeach
                                </tr>
                                <tr>
                                    <th scope="row">قیمت سالانه</th>
                                    @foreach ($plans as $plan)
                                    <td>{{ number_format($plan->price_yearly) }} تومان</td>
                                    @endforeach
                                </tr>
                                <tr>
                                    <th scope="row">مدت اعتبار</th>
                                    @foreach ($plans as $plan)
                                    <td>{{ $plan->duration_days ? number_format($plan->duration_days) . ' روز' : '—' }}</td>
                                    @endforeach
                                </tr>
                                <tr>
                                    <th scope="row">تعداد پیامک</th>
                                    @foreach ($plans as $plan)
                                    <td>{{ $plan->sms_count ? number_format($plan->sms_count) : 'نامحدود' }}</td>
                                    @endforeach
                                </tr>
                                <tr>
                                    <th scope="row">خطوط اختصاصی</th>
                                    @foreach ($plans as $plan)
                                    <td>{{ $plan->lines_count ? number_format($plan->lines_count) : 'نامحدود' }}</td>
                                    @endforeach
                                </tr>
                                <tr>
                                    <th scope="row">تعداد کاربر</th>
                                    @foreach ($plans as $plan)
                                    <td>{{ $plan->users_count ? number_format($plan->users_count) : 'نامحدود' }}</td>
                                    @endforeach
                                </tr>
                                @foreach ($allFeatures as $feature)
                                <tr>
                                    <th scope="row">{{ $feature }}</th>
                                    @foreach ($plans as $plan)
                                    <td>
                                        @if (in_array($feature, $plan->feature_list, true))
                                        <span class="tick yes" aria-label="دارد">✓</span>
                                        @else
                                        <span class="tick no" aria-label="ندارد">—</span>
                                        @endif
                                    </td>
                                    @endforeach
                                </tr>
                                @endforeach
                                <tr>
                                    <th scope="row"></th>
                                    @foreach ($plans as $plan)
                                    <td>
                                        <a href="{{ $plan->cta_url ?: route('home') . '/#cta' }}" class="btn {{ $plan->cta_style }} full">{{ $plan->cta_label }}</a>
                                    </td>
                                    @endforeach
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
            @endif

            <section class="section faq-section" aria-labelledby="pricing-faq-title">
                <div class="container">
                    <div class="section-heading center">
                        <span class="kicker">سوالات متداول</span>
                        <h2 id="pricing-faq-title">سوالات رایج درباره تعرفه‌ها</h2>
                    </div>

                    <div class="faq-list" itemscope itemtype="https://schema.org/FAQPage">
                        @foreach ($faqs as $i => $faq)
                        <details @if ($i === 0) open @endif itemprop="mainEntity" itemscope itemtype="https://schema.org/Question">
                            <summary itemprop="name">{{ $faq['q'] }}</summary>
                            <div itemprop="acceptedAnswer" itemscope itemtype="https://schema.org/Answer">
                                <p itemprop="text">{{ $faq['a'] }}</p>
                            </div>
                        </details>
                        @endforeach
                    </div>
                </div>
            </section>

            <section id="cta" class="section cta-section">
                <div class="container cta-box">
                    <div>
                        <span class="kicker light">هنوز مطمئن نیستید؟</span>
                        <h2>برای انتخاب پلن مناسب با ما مشورت کنید</h2>
                    </div>
                    <div class="cta-actions">
                        <a class="btn btn-primary" href="mailto:{{ $email }}">{{ $email }}</a>
                        <a class="btn btn-secondary light" href="{{ route('home') }}">بازگشت به صفحه اصلی</a>
                    </div>
                </div>
            </section>
        </main>

        @include('partials.site-footer')
    </div>
</body>

</html>
