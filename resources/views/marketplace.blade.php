@php
/**
* Public marketing page for «بازارچه» ("/marketplace"). Standalone
* document (own

<head>) like the pricing / lines pages. Content is
    * database-driven (marketplace_apps) and managed from the Filament admin
    * panel — see docs/starter.md §15.
    */
    $brand = config('theme.brand');
    $seo = config('theme.seo');
    $primary = config('theme.primary');
    $email = config('theme.email');
    $url = rtrim($seo['url'], '/');
    $canonical = route('marketplace');

    $metaTitle = 'بازارچهی ' . $brand . ' | افزودن قابلیت به پنل پیامک';
    $metaDescription = 'افزونه‌های کسب‌وکار ' . $brand
    . ' — اتصال به ایرپلاس، کارت ویزیت الکترونیکی، منشی پیامکی و ابزارهای بیشتر. با چند کلیک به پنل پیامک خود اضافه کنید.';

    $ctaHref = auth()->check() ? route('dashboard.marketplace') : route('register');

    $graph = [
    [
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
    ['@type' => 'ListItem', 'position' => 1, 'name' => 'خانه', 'item' => $url . '/'],
    ['@type' => 'ListItem', 'position' => 2, 'name' => 'بازارچه', 'item' => $canonical],
    ],
    ],
    ];

    if ($apps->isNotEmpty()) {
    $graph[] = [
    '@type' => 'ItemList',
    'name' => 'بازارچهی ' . $brand,
    'itemListElement' => $apps->values()->map(fn ($app, $i) => [
    '@type' => 'ListItem',
    'position' => $i + 1,
    'item' => [
    '@type' => 'Product',
    'name' => $app->name,
    'description' => $app->tagline ?: $app->name,
    'brand' => ['@type' => 'Brand', 'name' => $app->vendor ?: $brand],
    'category' => $app->category_label,
    ],
    ])->all(),
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
                        <span>بازارچه</span>
                    </nav>

                    <div class="section-heading center">
                        <span class="kicker">بازارچه</span>
                        <h1>پنل پیامک {{ $brand }} را با افزونه‌ها گسترش دهید</h1>
                        <p>
                            قابلیت‌های تازه را بدون تغییر در سرویس فعلی به حساب خود اضافه کنید — از
                            اتصال به سرویس‌های بیرونی مثل ایرپلاس تا ابزارهای داخلی مثل کارت ویزیت و
                            منشی پیامکی. فعال‌سازی چند کلیک است و هر افزونه از داخل پنل مدیریت می‌شود.
                        </p>
                    </div>

                    <div class="hero-actions" style="justify-content: center">
                        <a class="btn btn-primary" href="{{ $ctaHref }}">{{ auth()->check() ? 'ورود به بازارچه' : 'ساخت حساب رایگان' }}</a>
                        <a class="btn btn-secondary" href="#apps">مشاهده افزونه‌ها</a>
                    </div>
                </div>
            </section>

            @if ($featured)
            <section class="section alt-bg" aria-labelledby="mk-spotlight-title">
                <div class="container">
                    <div class="mk-spotlight" @if ($featured->accent_color) style="--mk-accent: {{ $featured->accent_color }}" @endif>
                        <div class="mk-spotlight__badge">پیشنهاد ویژه</div>
                        <div class="mk-spotlight__body">
                            <div class="mk-ico lg" aria-hidden="true">{!! $featured->icon_html !!}</div>
                            <div>
                                <h2 id="mk-spotlight-title">{{ $featured->name }}</h2>
                                @if ($featured->vendor)
                                <p class="mk-app__vendor">ارائه‌دهنده: {{ $featured->vendor }}</p>
                                @endif
                                <p>{{ $featured->tagline }}</p>
                                <div class="mk-spotlight__foot">
                                    <span class="mk-app__price">{{ $featured->price_label }}</span>
                                    <a class="btn btn-primary" href="{{ auth()->check() ? route('marketplace.show', $featured) : route('register') }}">
                                        {{ $featured->isFree() ? 'افزودن افزونه' : 'مشاهده و خرید' }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            @endif

            <section id="apps" class="section" aria-labelledby="mk-apps-title">
                <div class="container">
                    <div class="section-heading center">
                        <span class="kicker">همه افزونه‌ها</span>
                        <h2 id="mk-apps-title">افزونه مناسب کسب‌وکار خود را انتخاب کنید</h2>
                    </div>

                    @if ($apps->isEmpty())
                    <p class="pricing-empty">به‌زودی افزونه‌های تازه در این بخش منتشر می‌شوند.</p>
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
                        @foreach ($apps as $app)
                        <article class="mk-app" data-cat="{{ $app->category }}"
                            @if ($app->accent_color) style="--mk-accent: {{ $app->accent_color }}" @endif>
                            @if ($app->is_featured)
                            <span class="mk-app__tag">پیشنهادی</span>
                            @endif
                            <div class="mk-ico" aria-hidden="true">{!! $app->icon_html !!}</div>
                            <h3>{{ $app->name }}</h3>
                            <span class="mk-app__cat">{{ $app->category_label }}</span>
                            @if ($app->vendor)
                            <p class="mk-app__vendor">ارائه‌دهنده: {{ $app->vendor }}</p>
                            @endif
                            @if ($app->tagline)
                            <p class="mk-app__desc">{{ $app->tagline }}</p>
                            @endif
                            <div class="mk-app__foot">
                                <span class="mk-app__price">{{ $app->price_label }}</span>
                                <a class="btn btn-secondary" href="{{ auth()->check() ? route('marketplace.show', $app) : route('register') }}">
                                    {{ auth()->check() ? ($app->isFree() ? 'افزودن' : 'مشاهده') : 'ورود / ثبت‌نام' }}
                                </a>
                            </div>
                        </article>
                        @endforeach
                    </div>

                    <p class="mk-empty-state" id="mk-empty-state" hidden>افزونه‌ای در این دسته پیدا نشد.</p>
                    @endif
                </div>
            </section>

            <section class="section alt-bg" aria-labelledby="mk-how-title">
                <div class="container">
                    <div class="section-heading center">
                        <span class="kicker">چطور کار می‌کند</span>
                        <h2 id="mk-how-title">فعال‌سازی افزونه در سه گام</h2>
                    </div>

                    <div class="steps-row">
                        <article class="step-card">
                            <span class="step-num">۱</span>
                            <h3>انتخاب افزونه</h3>
                            <p>از داخل پنل، افزونه دلخواه را باز کنید و توضیحات و دسترسی‌های آن را ببینید.</p>
                        </article>
                        <article class="step-card">
                            <span class="step-num">۲</span>
                            <h3>اتصال یا پرداخت</h3>
                            <p>اطلاعات اتصال (مثل کلید API) را وارد کنید یا هزینه را از کیف پول/درگاه بپردازید.</p>
                        </article>
                        <article class="step-card">
                            <span class="step-num">۳</span>
                            <h3>استفاده در پنل</h3>
                            <p>قابلیت تازه بلافاصله در منوی پنل فعال می‌شود و از همان‌جا مدیریت می‌گردد.</p>
                        </article>
                    </div>
                </div>
            </section>

            <section id="cta" class="section cta-section">
                <div class="container cta-box">
                    <div>
                        <span class="kicker light">آماده شروع هستید؟</span>
                        <h2>حساب {{ $brand }} بسازید و بازارچه را از داخل پنل ببینید</h2>
                    </div>
                    <div class="cta-actions">
                        <a class="btn btn-primary" href="{{ $ctaHref }}">{{ auth()->check() ? 'ورود به پنل' : 'ساخت حساب رایگان' }}</a>
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