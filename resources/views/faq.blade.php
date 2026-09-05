@php
    // $faqs and $jsonLd (BreadcrumbList + FAQPage) come from FaqController::index().
    $brand = config('theme.brand');
    $seo = config('theme.seo');
    $primary = config('theme.primary');
    $email = config('theme.email');
    $url = rtrim($seo['url'], '/');
    $canonical = route('faq');

    $metaTitle = 'سوالات متداول ' . $brand . ' | راهنمای سریع پنل پیامک';
    $metaDescription = 'پاسخ به پرتکرارترین سوالات درباره ثبت‌نام، پلن‌ها، خطوط اختصاصی، پرداخت و امکانات پنل پیامک ' . $brand . '.';
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
                        <span>سوالات متداول</span>
                    </nav>

                    <div class="section-heading center">
                        <span class="kicker">سوالات متداول</span>
                        <h1>پاسخ به پرتکرارترین سوالات شما</h1>
                        <p>اگر پاسخ سوال خود را اینجا پیدا نکردید، از <a href="{{ route('contact') }}">صفحه تماس با ما</a> با ما در ارتباط باشید.</p>
                    </div>

                    <div class="faq-list" style="max-width: 760px; margin: 0 auto;" itemscope itemtype="https://schema.org/FAQPage">
                        @forelse ($faqs as $i => $faq)
                            <details @if ($i === 0) open @endif itemprop="mainEntity" itemscope itemtype="https://schema.org/Question">
                                <summary itemprop="name">{{ $faq->question }}</summary>
                                <div itemprop="acceptedAnswer" itemscope itemtype="https://schema.org/Answer">
                                    <p itemprop="text">{{ $faq->answer }}</p>
                                </div>
                            </details>
                        @empty
                            <p class="pricing-empty">به‌زودی سوالات متداول در این بخش نمایش داده می‌شوند.</p>
                        @endforelse
                    </div>
                </div>
            </section>

            <section id="cta" class="section cta-section">
                <div class="container cta-box">
                    <div>
                        <span class="kicker light">سوال دیگری دارید؟</span>
                        <h2>تیم پشتیبانی {{ $brand }} پاسخگوی شماست</h2>
                    </div>
                    <div class="cta-actions">
                        <a class="btn btn-primary" href="{{ route('contact') }}">تماس با ما</a>
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
