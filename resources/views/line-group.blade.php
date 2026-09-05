@php
    // The LineGroup, its lines and its bundles + the JSON-LD graph are prepared
    // in LineController::group() — docs/lines-landing.md. This view only reads
    // config/theme values plus the model attributes.
    $brand = config('theme.brand');
    $seo = config('theme.seo');
    $primary = config('theme.primary');
    $email = config('theme.email');
    $phoneDisplay = config('theme.phone_display');
    $url = rtrim($seo['url'], '/');

    $metaTitle = $group->seo_title ?: ($group->title . ' | ' . $brand);
    $metaDescription = $group->seo_description
        ?: ($group->tagline ?: ('خرید و فعال‌سازی ' . $group->title . ' — مشاهده گونه‌ها، قیمت‌ها و باندل‌های آماده و ثبت سفارش آنلاین در ' . $brand . '.'));
    $ogImage = $group->og_image ?: ($url . $seo['image']);
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
    <meta property="og:image" content="{{ $ogImage }}" />
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="{{ $metaTitle }}" />
    <meta name="twitter:description" content="{{ $metaDescription }}" />
    <meta name="twitter:image" content="{{ $ogImage }}" />

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
                        <a href="{{ route('lines') }}">خطوط اختصاصی</a>
                        <span aria-hidden="true">/</span>
                        <span>{{ $group->title }}</span>
                    </nav>

                    <div class="section-heading center">
                        <span class="kicker">خط اختصاصی {{ $group->prefix }}</span>
                        <h1>{{ $group->title }}</h1>
                        @if ($group->tagline)
                            <p>{{ $group->tagline }}</p>
                        @endif
                    </div>

                    <div class="line-group-cta">
                        <a class="btn btn-outline" href="{{ route('lines') }}">مشاهده همه خطوط</a>
                        @if ($lines->isNotEmpty())
                            <a class="btn btn-primary" href="#buy">مشاهده قیمت‌ها</a>
                        @endif
                    </div>
                </div>
            </section>

            @if ($group->rendered_body)
                <section class="section" aria-labelledby="line-group-about">
                    <div class="container">
                        <div class="prose line-group-prose">
                            {!! $group->rendered_body !!}
                        </div>
                    </div>
                </section>
            @endif

            @if ($group->feature_list || $group->use_case_list)
                <section class="section alt-bg" aria-labelledby="line-group-specs">
                    <div class="container">
                        <div class="line-group-specs">
                            @if ($group->feature_list)
                                <div>
                                    <h2 id="line-group-specs">ویژگی‌های خط {{ $group->prefix }}</h2>
                                    <ul class="line-group-list">
                                        @foreach ($group->feature_list as $feature)
                                            <li>{{ $feature }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @if ($group->use_case_list)
                                <div>
                                    <h2>مناسب چه کسب‌وکارهایی است؟</h2>
                                    <ul class="line-group-list">
                                        @foreach ($group->use_case_list as $useCase)
                                            <li>{{ $useCase }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>
                    </div>
                </section>
            @endif

            <section class="section" id="buy" aria-labelledby="line-group-buy">
                <div class="container">
                    <div class="section-heading center">
                        <span class="kicker">گونه‌های خرید</span>
                        <h2 id="line-group-buy">گونه‌ها و قیمت خط {{ $group->prefix }}</h2>
                        <p>تعداد ارقام و نوع خط دلخواه را انتخاب کنید، قیمت را ببینید و سفارش را ثبت کنید.</p>
                    </div>

                    @if ($lines->isEmpty())
                        <p class="pricing-empty">به‌زودی گونه‌های این خط اضافه می‌شوند. برای استعلام با پشتیبانی تماس بگیرید.</p>
                    @else
                        <div class="line-cards">
                            @foreach ($lines as $line)
                                <article class="line-card">
                                    <header class="line-card-head">
                                        <span class="line-card-num">{{ $line->display_number }}<span class="sub">{{ $line->display_number_x }}</span></span>
                                        <span class="line-card-prefix">خطوط {{ $line->prefix }}</span>
                                    </header>

                                    <ul class="line-card-meta">
                                        <li>{{ $line->digits }} رقمی</li>
                                        <li>{{ $line->type_label }}</li>
                                        @if ($line->operator)<li>{{ $line->operator }}</li>@endif
                                        @if ($line->is_rond)<li class="is-rond">رند</li>@endif
                                    </ul>

                                    @if ($line->feature_list)
                                        <ul class="line-card-features">
                                            @foreach (array_slice($line->feature_list, 0, 3) as $feature)
                                                <li>{{ $feature }}</li>
                                            @endforeach
                                        </ul>
                                    @endif

                                    <div class="line-card-foot">
                                        @if ($line->requires_inquiry)
                                            <span class="line-card-price inquiry">استعلام قیمت</span>
                                        @else
                                            <span class="line-card-price">
                                                @if ($line->compare_at_price)
                                                    <del>{{ number_format($line->compare_at_price) }}</del>
                                                @endif
                                                <strong>{{ number_format($line->price) }}</strong>
                                                <span class="unit">تومان</span>
                                            </span>
                                        @endif

                                        @if ($line->sale_status === 'available')
                                            <a class="btn btn-primary full" href="{{ route('lines.checkout', $line) }}">
                                                {{ $line->requires_inquiry ? 'درخواست استعلام' : 'خرید خط' }}
                                            </a>
                                        @else
                                            <span class="pill dark">{{ $line->sale_status_label }}</span>
                                        @endif
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @endif
                </div>
            </section>

            @if ($bundles->isNotEmpty())
                <section class="section alt-bg" id="bundles" aria-labelledby="line-group-bundles">
                    <div class="container">
                        <div class="section-heading center">
                            <span class="kicker">باندل‌های آماده</span>
                            <h2 id="line-group-bundles">باندل خط {{ $group->prefix }} + پیامک</h2>
                            <p>خط اختصاصی به‌همراه اعتبار پیامک و مدت فعال‌سازی، در یک بسته با قیمت مقطوع.</p>
                        </div>

                        <div class="line-bundle-cards">
                            @foreach ($bundles as $bundle)
                                <article class="line-bundle-card @if ($bundle->badge_label) is-featured @endif">
                                    @if ($bundle->badge_label)
                                        <span class="line-bundle-badge">{{ $bundle->badge_label }}</span>
                                    @endif

                                    <h3>{{ $bundle->title }}</h3>
                                    @if ($bundle->description)
                                        <p class="line-bundle-desc">{{ $bundle->description }}</p>
                                    @endif

                                    <ul class="line-bundle-meta">
                                        @if ($bundle->sms_credit)
                                            <li><span>اعتبار پیامک</span><strong>{{ number_format($bundle->sms_credit) }} عدد</strong></li>
                                        @endif
                                        @if ($bundle->validity_days)
                                            <li><span>مدت اعتبار</span><strong>{{ number_format($bundle->validity_days) }} روز</strong></li>
                                        @endif
                                        @if ($bundle->smsLine)
                                            <li><span>خط</span><strong>{{ $bundle->smsLine->prefix }} — {{ $bundle->smsLine->digits }} رقمی</strong></li>
                                        @endif
                                    </ul>

                                    @if ($bundle->feature_list)
                                        <ul class="line-bundle-features">
                                            @foreach ($bundle->feature_list as $feature)
                                                <li>{{ $feature }}</li>
                                            @endforeach
                                        </ul>
                                    @endif

                                    <div class="line-bundle-foot">
                                        <span class="line-card-price">
                                            @if ($bundle->compare_at_price)
                                                <del>{{ number_format($bundle->compare_at_price) }}</del>
                                            @endif
                                            <strong>{{ number_format($bundle->price) }}</strong>
                                            <span class="unit">تومان</span>
                                        </span>
                                        <a class="btn btn-primary full" href="{{ route('lines.bundle.checkout', [$group, $bundle]) }}">خرید باندل</a>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </div>
                </section>
            @endif

            @if ($group->faq_list)
                <section class="section" aria-labelledby="line-group-faq">
                    <div class="container">
                        <div class="section-heading center">
                            <span class="kicker">پرسش‌های متداول</span>
                            <h2 id="line-group-faq">سوالات رایج درباره خط {{ $group->prefix }}</h2>
                        </div>

                        <div class="line-group-faq">
                            @foreach ($group->faq_list as $faq)
                                <details>
                                    <summary>{{ $faq['q'] }}</summary>
                                    <p>{{ $faq['a'] }}</p>
                                </details>
                            @endforeach
                        </div>
                    </div>
                </section>
            @endif

            <section class="section alt-bg" aria-labelledby="line-group-help">
                <div class="container">
                    <div class="section-heading center">
                        <span class="kicker">راهنما</span>
                        <h2 id="line-group-help">هنوز مطمئن نیستید کدام خط مناسب شماست؟</h2>
                        <p>کارشناسان ما در انتخاب پیش‌شماره، تعداد ارقام و باندل مناسب کسب‌وکارتان کمک می‌کنند.</p>
                    </div>
                    <p class="line-help-contact">تماس با پشتیبانی: <a href="tel:{{ config('theme.phone') }}">{{ $phoneDisplay }}</a> یا <a href="mailto:{{ $email }}">{{ $email }}</a></p>
                </div>
            </section>
        </main>

        @include('partials.site-footer')
    </div>

    @include('partials.flash')
</body>

</html>
