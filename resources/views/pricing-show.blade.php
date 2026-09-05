@php
    // Everything is prepared in PricingController::show() — this view only
    // reads config/theme values, mirroring pricing.blade.php's structure but
    // scoped to a single plan so it can be indexed as its own product page.
    $brand = config('theme.brand');
    $seo = config('theme.seo');
    $primary = config('theme.primary');
    $email = config('theme.email');
    $url = rtrim($seo['url'], '/');
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

    <meta property="og:type" content="product" />
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
                        <a href="{{ route('pricing') }}">تعرفه‌ها</a>
                        <span aria-hidden="true">/</span>
                        <span>{{ $plan->name }}</span>
                    </nav>

                    <div class="section-heading center">
                        <span class="kicker">پلن {{ $plan->name }}</span>
                        <h1>پلن {{ $plan->name }} {{ $brand }}</h1>
                        @if ($plan->description)
                        <p>{{ $plan->description }}</p>
                        @endif
                    </div>

                    <div class="pricing-grid" style="max-width: 420px; margin: 0 auto;">
                        <article class="pricing-card @if ($plan->is_featured) featured @endif"
                            @if ($plan->color) style="--card-accent: {{ $plan->color }}" @endif>
                            <div class="pricing-header">
                                <h3>{{ $plan->name }}</h3>
                                @if ($plan->badge_label)
                                <span class="pill {{ $plan->badge_style }}">{{ $plan->badge_label }}</span>
                                @endif
                            </div>
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
                            @include('partials.plan-cta', ['plan' => $plan])
                        </article>
                    </div>
                </div>
            </section>

            <section class="section alt-bg" aria-labelledby="spec-title">
                <div class="container">
                    <div class="section-heading center">
                        <span class="kicker">مشخصات</span>
                        <h2 id="spec-title">مشخصات کامل پلن {{ $plan->name }}</h2>
                    </div>

                    <div class="compare-wrap">
                        <table class="plan-spec-table">
                            <tbody>
                                <tr>
                                    <th scope="row">قیمت ماهانه</th>
                                    <td>{{ number_format($plan->price_monthly) }} تومان</td>
                                </tr>
                                <tr>
                                    <th scope="row">قیمت سالانه</th>
                                    <td>{{ number_format($plan->price_yearly) }} تومان</td>
                                </tr>
                                <tr>
                                    <th scope="row">مدت اعتبار</th>
                                    <td>{{ $plan->duration_days ? number_format($plan->duration_days) . ' روز' : '—' }}</td>
                                </tr>
                                <tr>
                                    <th scope="row">تعداد پیامک</th>
                                    <td>{{ $plan->sms_count ? number_format($plan->sms_count) : 'نامحدود' }}</td>
                                </tr>
                                <tr>
                                    <th scope="row">خطوط اختصاصی</th>
                                    <td>{{ $plan->lines_count ? number_format($plan->lines_count) : 'نامحدود' }}</td>
                                </tr>
                                <tr>
                                    <th scope="row">تعداد کاربر</th>
                                    <td>{{ $plan->users_count ? number_format($plan->users_count) : 'نامحدود' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            @if ($otherPlans->isNotEmpty())
            <section class="section" aria-labelledby="other-plans-title">
                <div class="container">
                    <div class="section-heading center">
                        <span class="kicker">سایر پلن‌ها</span>
                        <h2 id="other-plans-title">پلن‌های دیگر {{ $brand }}</h2>
                    </div>

                    <div class="other-plans-grid">
                        @foreach ($otherPlans as $other)
                        <a class="other-plan-card" href="{{ route('pricing.show', $other->slug) }}">
                            <span>
                                <strong>{{ $other->name }}</strong>
                                <span>{{ number_format($other->price_monthly) }} تومان/ماه</span>
                            </span>
                        </a>
                        @endforeach
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
                        <a class="btn btn-secondary light" href="{{ route('pricing') }}">مقایسه همه پلن‌ها</a>
                    </div>
                </div>
            </section>
        </main>

        @include('partials.site-footer')
    </div>

    @include('partials.flash')
</body>

</html>
