@php
    $brand = config('theme.brand');
    $seo = config('theme.seo');
    $primary = config('theme.primary');
    $email = config('theme.email');
    $url = rtrim($seo['url'], '/');
    $canonical = route('ussd');

    $metaTitle = 'کد دستوری USSD ' . $brand . ' | راه‌اندازی و فروش کد دستوری';
    $metaDescription = 'خرید و فعال‌سازی کد دستوری USSD اختصاصی برای کسب‌وکار شما — منوی دلخواه، پرداخت آفلاین و آنلاین، بدون نیاز به اینترنت کاربر.';

    $graph = [
        [
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'خانه', 'item' => $url . '/'],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'کد دستوری USSD', 'item' => $canonical],
            ],
        ],
    ];

    foreach ($plans as $plan) {
        $graph[] = [
            '@type' => 'Service',
            'name' => 'کد دستوری ' . $plan->name . ' ' . $brand,
            'description' => $plan->description ?: implode('، ', $plan->feature_list),
            'brand' => ['@type' => 'Brand', 'name' => $brand],
            'provider' => ['@type' => 'Organization', 'name' => $brand],
            'offers' => [
                '@type' => 'Offer',
                'price' => $plan->price_monthly * 10, // Toman -> Rial for ISO 4217
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
                        <span>کد دستوری USSD</span>
                    </nav>

                    <div class="section-heading center">
                        <span class="kicker">کد دستوری USSD</span>
                        <h1>کسب‌وکار خود را بدون نیاز به اینترنت در دسترس مشتریان قرار دهید</h1>
                        <p>
                            با کد دستوری اختصاصی، مشتریان بدون اینترنت و تنها با شماره‌گیری، به منوی خدمات شما
                            دسترسی پیدا می‌کنند — سبد خرید، نظرسنجی، نوبت‌دهی و پرداخت آنلاین یا آفلاین.
                        </p>
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
                                    <span class="price">{{ number_format($plan->price_monthly) }}</span>
                                </div>
                                <ul>
                                    @foreach ($plan->feature_list as $feature)
                                        <li>{{ $feature }}</li>
                                    @endforeach
                                </ul>
                                @include('partials.plan-cta', ['plan' => $plan])
                            </article>
                        @empty
                            <p class="pricing-empty">به‌زودی پلن‌های کد دستوری در این بخش نمایش داده می‌شوند.</p>
                        @endforelse
                    </div>
                </div>
            </section>

            <section id="cta" class="section cta-section">
                <div class="container cta-box">
                    <div>
                        <span class="kicker light">پلن سفارشی نیاز دارید؟</span>
                        <h2>برای انتخاب کد دستوری مناسب کسب‌وکار با ما مشورت کنید</h2>
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

    @include('partials.flash')
</body>

</html>
