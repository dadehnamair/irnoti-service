@php
    // $features, $featured, $groups, $categories and $jsonLd come from FeaturesController::index().
    $brand = config('theme.brand');
    $seo = config('theme.seo');
    $primary = config('theme.primary');
    $email = config('theme.email');
    $url = rtrim($seo['url'], '/');
    $canonical = route('features');

    $metaTitle = 'امکانات ' . $brand . ' | همه قابلیت‌های پنل پیامک';
    $metaDescription = 'همه امکانات پنل پیامک ' . $brand . ' در یک نگاه — ارسال انبوه، خطوط اختصاصی، کیف پول، بازارچه، کارت ویزیت دیجیتال، پیام‌رسان‌ها و نمایندگی فروش.';
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
                        <span>امکانات</span>
                    </nav>

                    <div class="section-heading center">
                        <span class="kicker">امکانات</span>
                        <h1>هر چیزی که برای ارتباط پیامکی با مشتری لازم دارید</h1>
                        <p>از ارسال انبوه تا کارت ویزیت دیجیتال و نمایندگی فروش — همه امکانات {{ $brand }} در یک صفحه.</p>
                    </div>
                </div>
            </section>

            @if ($featured)
                <section class="section alt-bg" aria-labelledby="feature-spotlight-title">
                    <div class="container">
                        <div class="mk-spotlight">
                            <div class="mk-spotlight__badge">{{ $featured->badge ?: 'پیشنهاد ویژه' }}</div>
                            <div class="mk-spotlight__body">
                                <div class="mk-ico lg" aria-hidden="true">{{ $featured->icon ?: '🧩' }}</div>
                                <div>
                                    <h2 id="feature-spotlight-title">{{ $featured->title }}</h2>
                                    @if ($featured->tagline)
                                        <p class="mk-app__vendor">{{ $featured->tagline }}</p>
                                    @endif
                                    <p>{{ $featured->description }}</p>
                                    @if ($featured->href)
                                        <div class="mk-spotlight__foot">
                                            <a class="btn btn-primary" href="{{ $featured->href }}">مشاهده</a>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            @endif

            <section id="all-features" class="section" aria-labelledby="all-features-title">
                <div class="container">
                    <div class="section-heading center">
                        <span class="kicker">همه امکانات</span>
                        <h2 id="all-features-title">دسته‌بندی کامل قابلیت‌ها</h2>
                    </div>

                    @if ($features->isEmpty())
                        <p class="pricing-empty">به‌زودی امکانات در این بخش نمایش داده می‌شوند.</p>
                    @else
                        <div class="mk-filter-bar" role="group" aria-label="فیلتر دسته‌بندی">
                            <button type="button" class="mk-filter is-active" data-cat="all" aria-pressed="true">همه</button>
                            @foreach ($groups as $cat => $group)
                                <button type="button" class="mk-filter" data-cat="{{ $cat }}" aria-pressed="false">
                                    {{ $categories[$cat] ?? $cat }}
                                </button>
                            @endforeach
                        </div>

                        <div class="mk-grid" id="mk-grid">
                            @foreach ($features as $feature)
                                <article class="mk-app" data-cat="{{ $feature->category }}">
                                    @if ($feature->badge)
                                        <span class="mk-app__tag">{{ $feature->badge }}</span>
                                    @endif
                                    <div class="mk-ico" aria-hidden="true">{{ $feature->icon ?: '🧩' }}</div>
                                    <h3>{{ $feature->title }}</h3>
                                    <span class="mk-app__cat">{{ $feature->category_label }}</span>
                                    @if ($feature->tagline)
                                        <p class="mk-app__vendor">{{ $feature->tagline }}</p>
                                    @endif
                                    @if ($feature->description)
                                        <p class="mk-app__desc">{{ $feature->description }}</p>
                                    @endif
                                    @if ($feature->href)
                                        <div class="mk-app__foot">
                                            <a class="btn btn-secondary" href="{{ $feature->href }}">اطلاعات بیشتر</a>
                                        </div>
                                    @endif
                                </article>
                            @endforeach
                        </div>

                        <p class="mk-empty-state" id="mk-empty-state" hidden>موردی در این دسته پیدا نشد.</p>
                    @endif
                </div>
            </section>

            <section id="cta" class="section cta-section">
                <div class="container cta-box">
                    <div>
                        <span class="kicker light">آماده شروع هستید؟</span>
                        <h2>همین حالا حساب کاربری خود را در {{ $brand }} بسازید</h2>
                    </div>
                    <div class="cta-actions">
                        <a class="btn btn-primary" href="{{ auth()->check() ? route('dashboard') : route('register') }}">ساخت حساب رایگان</a>
                        <a class="btn btn-secondary light" href="mailto:{{ $email }}">{{ $email }}</a>
                    </div>
                </div>
            </section>
        </main>

        @include('partials.site-footer')
    </div>

    @include('partials.flash')
</body>

</html>
