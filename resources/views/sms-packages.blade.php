@php
    // Packages, meta copy and the JSON-LD graph are all prepared in
    // PricingController::packages() — this view only reads config/theme values.
    $brand = config('theme.brand');
    $seo = config('theme.seo');
    $primary = config('theme.primary');
    $canonical = route('sms-packages');
@endphp
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="robots" content="index, follow, max-image-preview:large" />
    <meta name="theme-color" content="{{ $primary }}" />
    <meta name="description" content="{{ $metaDescription }}" />
    <meta property="og:type" content="website" />
    <meta property="og:site_name" content="{{ $brand }}" />
    <meta property="og:title" content="{{ $metaTitle }}" />
    <meta property="og:description" content="{{ $metaDescription }}" />
    <meta property="og:url" content="{{ $canonical }}" />

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
                        <span>بسته‌های پیامکی</span>
                    </nav>

                    <div class="section-heading center">
                        <span class="kicker">بسته‌های پیامکی</span>
                        <h1>اعتبار پیامکی را یک‌جا و با قیمت مقطوع بخرید</h1>
                        <p>بسته‌های حجمی مناسب کمپین‌ها و ارسال انبوه؛ بدون دوره و اشتراک. پس از ورود، از پنل کاربری خرید کنید.</p>
                    </div>

                    <div class="pricing-grid">
                        @forelse ($packages as $package)
                            <article class="pricing-card @if ($package->is_featured) featured @endif">
                                <div class="pricing-header">
                                    <h3>{{ $package->name }}</h3>
                                    @if ($package->badge_label)
                                        <span class="pill">{{ $package->badge_label }}</span>
                                    @endif
                                </div>
                                @if ($package->description)
                                    <p class="plan-desc">{{ $package->description }}</p>
                                @endif
                                <div class="price-wrap">
                                    <span class="currency">تومان</span>
                                    @if ($package->compare_at_price)
                                        <span class="price-compare">{{ number_format($package->compare_at_price) }}</span>
                                    @endif
                                    <span class="price">{{ number_format($package->price) }}</span>
                                </div>
                                <ul>
                                    <li>{{ number_format($package->sms_count) }} پیامک</li>
                                    <li>هر پیامک {{ number_format($package->unit_price) }} تومان</li>
                                    <li>بدون تاریخ انقضا در حساب شما</li>
                                </ul>
                                <a class="btn btn-primary full" href="{{ route('dashboard.packages') }}">خرید از پنل کاربری</a>
                            </article>
                        @empty
                            <p class="pricing-empty">به‌زودی بسته‌های پیامکی در این بخش نمایش داده می‌شوند.</p>
                        @endforelse
                    </div>
                </div>
            </section>
        </main>

        @include('partials.site-footer')
    </div>
</body>

</html>
