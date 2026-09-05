@php
// Content-model queries, JSON-LD graph, and the FAQ list all live in
// HomeController — this view only reads config() for brand/theme display
// and the variables the controller already prepared.
$brand = config('theme.brand');
$seo = config('theme.seo');
$primary = config('theme.primary');
$accent = config('theme.accent');
$secondary = config('theme.secondary');
$email = config('theme.email');
$phone = config('theme.phone');
$phoneDisplay = config('theme.phone_display');
$nav = config('theme.nav');
$url = rtrim($seo['url'], '/');
@endphp
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1" />
    <meta name="theme-color" content="{{ $primary }}" />
    <meta name="author" content="{{ $brand }}" />
    <meta name="description" content="{{ $seo['description'] }}" />
    <meta name="keywords" content="{{ $seo['keywords'] }}" />

    <meta property="og:type" content="website" />
    <meta property="og:site_name" content="{{ $brand }}" />
    <meta property="og:locale" content="{{ $seo['locale'] }}" />
    <meta property="og:title" content="{{ $seo['title'] }}" />
    <meta property="og:description" content="{{ $seo['description'] }}" />
    <meta property="og:url" content="{{ $url }}/" />
    <meta property="og:image" content="{{ $url }}{{ $seo['image'] }}" />
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="{{ $seo['title'] }}" />
    <meta name="twitter:description" content="{{ $seo['description'] }}" />
    <meta name="twitter:image" content="{{ $url }}{{ $seo['image'] }}" />

    <link rel="canonical" href="{{ $url }}/" />
    <link rel="alternate" hreflang="fa-IR" href="{{ $url }}/" />
    <link rel="icon" href="/logo/favicon.png" type="image/png" />

    <title>{{ $seo['title'] }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800;900&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Runtime theme colors from config/theme.php — loaded last so it wins. Change THEME_PRIMARY in .env to re-theme the whole site. --}}
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
            <section class="hero section">
                <div class="container hero-grid">
                    <div class="hero-copy">
                        <p class="eyebrow">
                            <span class="dot"></span>
                            پلتفرم پیامکی کسب‌وکار — پنل، خطوط، افزونه و API
                        </p>
                        <h1>زیرساخت کامل پیامک برای کسب‌وکارها و فروشگاه‌های آنلاین</h1>
                        <p>
                            {{ $brand }} همه‌چیز را یک‌جا جمع کرده است: ارسال انبوه و گروهی، خطوط
                            اختصاصی، دفترچه تلفن با همگام‌سازی، کیف پول و فاکتور رسمی، بازارچه
                            افزونه‌ها و وب‌سرویس استاندارد — با گزارش لحظه‌ای و پشتیبانی حرفه‌ای.
                        </p>

                        <div class="hero-actions">
                            <a class="btn btn-primary" href="{{ auth()->check() ? route('dashboard') : route('register') }}">شروع رایگان</a>
                            <a class="btn btn-secondary" href="#pricing">مشاهده تعرفه‌ها</a>
                        </div>

                        <ul class="hero-trust" aria-label="مزیت‌ها">
                            <li>بدون قرارداد و پیش‌پرداخت</li>
                            <li>راه‌اندازی در چند دقیقه</li>
                            <li>فاکتور رسمی برای شرکت‌ها</li>
                        </ul>

                        <div class="hero-meta">
                            <div>
                                <strong>+۱۲٬۰۰۰</strong>
                                <span>پیامک ارسال شده</span>
                            </div>
                            <div>
                                <strong>۹۹.۹٪</strong>
                                <span>دقت ارسال</span>
                            </div>
                            <div>
                                <strong>۲۴/۷</strong>
                                <span>پشتیبانی</span>
                            </div>
                        </div>
                    </div>

                    <div class="hero-visual" aria-hidden="true">
                        <div class="dashboard-card main-panel">
                            <div class="panel-head">
                                <span class="status-pill live">آنلاین</span>
                                <span>داشبورد {{ $brand }}</span>
                            </div>

                            <div class="chart-box">
                                <div class="chart-bar bar-a"></div>
                                <div class="chart-bar bar-b"></div>
                                <div class="chart-bar bar-c"></div>
                                <div class="chart-bar bar-d"></div>
                                <div class="chart-bar bar-e"></div>
                                <div class="chart-bar bar-f"></div>
                            </div>

                            <div class="metric-row">
                                <div class="metric-item">
                                    <span>ارسال موفق</span>
                                    <strong>۸۴٪</strong>
                                </div>
                                <div class="metric-item highlight">
                                    <span>اعتبار کیف پول</span>
                                    <strong>۲.۴M</strong>
                                </div>
                            </div>
                        </div>

                        <div class="floating-card card-top">
                            <span class="mini-label">API</span>
                            <strong>تأیید سریع</strong>
                            <small>زمان پاسخ: ۲۵ms</small>
                        </div>

                        <div class="floating-card card-bottom">
                            <span class="mini-label">خطوط اختصاصی</span>
                            <strong>+۴۸ خط فعال</strong>
                        </div>
                    </div>
                </div>
            </section>

            <section class="logos section-sm">
                <div class="container">
                    <p>بعضی از کسب‌وکارها و تیم‌ها با {{ $brand }} رشد کرده‌اند</p>
                    <div class="logos-row" aria-label="مشتریان">
                        <span>digitalsale</span>
                        <span>novastart</span>
                        <span>smartcart</span>
                        <span>medico</span>
                        <span>pearl</span>
                    </div>
                </div>
            </section>

            <section class="stat-band" aria-label="آمار سرویس">
                <div class="container stat-grid">
                    <div class="stat-item">
                        <strong>۹۹.۹٪</strong>
                        <span>در دسترس بودن سرویس</span>
                    </div>
                    <div class="stat-item">
                        <strong>&lt; ۳۰ms</strong>
                        <span>میانگین پاسخ API</span>
                    </div>
                    <div class="stat-item">
                        <strong>+۴۸</strong>
                        <span>خط اختصاصی فعال</span>
                    </div>
                    <div class="stat-item">
                        <strong>۲۴/۷</strong>
                        <span>پشتیبانی انسانی</span>
                    </div>
                </div>
            </section>

            <section id="features" class="section" aria-labelledby="features-title">
                <div class="container">
                    <div class="section-heading center">
                        <span class="kicker">چرا {{ $brand }}؟</span>
                        <h2 id="features-title">هر چیزی که برای ارتباط پیامکی با مشتری لازم دارید</h2>
                    </div>

                    <div class="feature-grid">
                        @foreach ($siteFeatures as $feature)
                        <article class="feature-card">
                            <div class="icon-wrap blue" aria-hidden="true">{{ $feature->icon ?? '✨' }}</div>
                            <h3>{{ $feature->title }}</h3>
                            <p>{{ $feature->tagline ?? $feature->description ?? '' }}</p>
                        </article>
                        @endforeach
                    </div>

                    <div class="section-more center">
                        <a class="heading-link" href="{{ route('features') }}">مشاهده همه امکانات ←</a>
                    </div>
                </div>
            </section>

            <section id="panel" class="section alt-bg panel-tour" aria-labelledby="panel-title">
                <div class="container">
                    <div class="section-heading">
                        <span class="kicker">یک پنل، همه ابزارها</span>
                        <h2 id="panel-title">پنل کاربری {{ $brand }} از روز اول کامل است</h2>
                    </div>

                    <div class="tour-grid">
                        <ul class="tour-menu" aria-label="بخش‌های پنل">
                            <li class="is-active"><span class="tour-ico">📊</span> داشبورد و گزارش‌ها</li>
                            <li><span class="tour-ico">✉️</span> ارسال پیامک تکی و گروهی</li>
                            <li><span class="tour-ico">📨</span> پیام‌ها — دریافتی و ارسالی</li>
                            <li><span class="tour-ico">📇</span> دفترچه تلفن و گروه‌ها</li>
                            <li><span class="tour-ico">👛</span> کیف پول و تراکنش‌ها</li>
                            <li><span class="tour-ico">🧾</span> فاکتورها و فیش بانکی</li>
                            <li><span class="tour-ico">🧩</span> بازارچه</li>
                            <li><span class="tour-ico">📞</span> خطوط اختصاصی</li>
                        </ul>

                        <div class="tour-preview" aria-hidden="true">
                            <div class="tour-preview-head">
                                <span>گزارش امروز</span>
                                <span class="badge success">فعال</span>
                            </div>
                            <div class="tour-preview-rows">
                                <div class="tpr"><span>پیامک ارسال‌شده</span><strong>۵۸٬۴۲۱</strong></div>
                                <div class="tpr"><span>نرخ تحویل</span><strong>۹۶٪</strong></div>
                                <div class="tpr"><span>مخاطب فعال</span><strong>۱۲٬۳۴۰</strong></div>
                                <div class="tpr"><span>اعتبار کیف پول</span><strong>۲٬۴۰۰٬۰۰۰ تومان</strong></div>
                            </div>
                            <div class="progress" role="progressbar" aria-valuenow="82" aria-valuemin="0" aria-valuemax="100">
                                <span style="width: 82%"></span>
                            </div>
                            <small>افزایش ۱۸٪ نسبت به ماه گذشته</small>
                        </div>
                    </div>
                </div>
            </section>

            @if ($marketEnabled && $marketApps->isNotEmpty())
            <section id="marketplace" class="section market-teaser" aria-labelledby="market-title">
                <div class="container">
                    <div class="section-heading">
                        <span class="kicker">بازارچه</span>
                        <h2 id="market-title">پنل خود را با چند کلیک قدرتمندتر کنید</h2>
                        <a class="heading-link" href="{{ route('marketplace') }}">مشاهده همه افزونه‌ها ←</a>
                    </div>

                    <div class="mk-teaser-grid">
                        @foreach ($marketApps as $app)
                        <article class="mk-teaser-card" @if ($app->accent_color) style="--mk-accent: {{ $app->accent_color }}" @endif>
                            <div class="mk-ico" aria-hidden="true">{!! $app->icon_html !!}</div>
                            <h3>{{ $app->name }}</h3>
                            @if ($app->tagline)
                            <p>{{ \Illuminate\Support\Str::of($app->tagline)->squish()->limit(90) }}</p>
                            @endif
                            <span class="mk-teaser-price">{{ $app->price_label }}</span>
                        </article>
                        @endforeach
                    </div>

                    <div class="lines-cta">
                        <a class="btn btn-primary" href="{{ route('marketplace') }}">ورود به بازارچه</a>
                    </div>
                </div>
            </section>
            @endif

            <section class="section alt-bg" aria-labelledby="perf-title">
                <div class="container two-col">
                    <div class="content-block">
                        <span class="kicker">عملکرد حرفه‌ای</span>
                        <h2 id="perf-title">برای کسب‌وکارهایی که به اعتماد، سرعت و گزارش‌گیری اهمیت می‌دهند</h2>
                        <ul class="check-list">
                            <li>تحویل واقعی و گزارش دقیق وضعیت ارسال</li>
                            <li>مدیریت سطوح کاربران و دسترسی‌ها</li>
                            <li>پنل مدیریتی ساده، سریع و امن</li>
                            <li>پشتیبانی و مستندات API برای تیم توسعه</li>
                            <li>قیمت‌گذاری منعطف و پلن‌های قابل شخصی‌سازی</li>
                        </ul>
                    </div>

                    <div class="info-panel">
                        <div class="info-card">
                            <div class="label-row">
                                <span>امروز</span>
                                <span class="badge success">فعال</span>
                            </div>
                            <h3>نرخ ارسال</h3>
                            <div class="big-number">۵۸٬۴۲۱</div>
                            <div class="progress" role="progressbar" aria-valuenow="82" aria-valuemin="0" aria-valuemax="100">
                                <span style="width: 82%"></span>
                            </div>
                            <small>افزایش ۱۸٪ نسبت به ماه گذشته</small>
                        </div>
                    </div>
                </div>
            </section>

            <section id="how" class="section" aria-labelledby="how-title">
                <div class="container">
                    <div class="section-heading center">
                        <span class="kicker">شروع سریع</span>
                        <h2 id="how-title">در سه گام به مشتریان‌تان پیامک بدهید</h2>
                    </div>

                    <div class="steps-row">
                        <article class="step-card">
                            <span class="step-num">۱</span>
                            <h3>ثبت‌نام با موبایل</h3>
                            <p>شماره موبایل را وارد کنید و کد پیامکی را تأیید کنید — بدون فرم طولانی.</p>
                        </article>
                        <article class="step-card">
                            <span class="step-num">۲</span>
                            <h3>انتخاب پلن یا خط</h3>
                            <p>یک پلن مناسب یا خط اختصاصی انتخاب کنید و در صورت نیاز افزونه‌ها را فعال کنید.</p>
                        </article>
                        <article class="step-card">
                            <span class="step-num">۳</span>
                            <h3>ارسال و گزارش‌گیری</h3>
                            <p>از پنل یا API پیامک بفرستید و وضعیت تحویل را لحظه‌ای دنبال کنید.</p>
                        </article>
                    </div>
                </div>
            </section>

            <section id="lines" class="section alt-bg" aria-labelledby="lines-title">
                <div class="container">
                    <div class="section-heading">
                        <span class="kicker">خطوط اختصاصی</span>
                        <h2 id="lines-title">دسترسی به خطوط و شماره‌های مناسب کسب‌وکار شما</h2>
                    </div>

                    <div class="line-grid">
                        @foreach ($lines as $line)
                        <a class="line-tag" href="{{ route('lines') }}">خطوط {{ $line }}</a>
                        @endforeach
                    </div>

                    <div class="lines-cta">
                        <a class="btn btn-primary" href="{{ route('lines') }}">مشاهده و خرید خطوط اختصاصی</a>
                    </div>
                </div>
            </section>

            <section id="business-cards" class="section" aria-labelledby="business-cards-title">
                <div class="container">
                    <div class="section-heading">
                        <span class="kicker">کارت ویزیت دیجیتال</span>
                        <h2 id="business-cards-title">معرفی حرفه‌ای خودتان با یک لینک</h2>
                    </div>

                    <div class="line-grid">
                        <a class="line-tag" href="{{ auth()->check() ? route('dashboard.cards.create') : route('register') }}">VBC — کارت ویزیت مجازی</a>
                        <a class="line-tag" href="{{ auth()->check() ? route('dashboard.cards.create') : route('register') }}">EBC — کارت ویزیت الکترونیکی (کد اختصاصی)</a>
                    </div>

                    <p class="pricing-note">
                        کارت ویزیت مجازی VBC از {{ number_format($businessCardPrice) }} تومان؛ کارت ویزیت الکترونیکی EBC با کد دلخواه روی دامنه‌های ویژه.
                    </p>

                    <div class="lines-cta">
                        <a class="btn btn-primary" href="{{ auth()->check() ? route('dashboard.cards.create') : route('register') }}">ساخت کارت ویزیت دیجیتال</a>
                    </div>
                </div>
            </section>

            <section id="pricing" class="section" aria-labelledby="pricing-title">
                <div class="container">
                    <div class="section-heading center">
                        <span class="kicker">تعرفه‌ها</span>
                        <h2 id="pricing-title">پلن مناسب کسب‌وکار شما</h2>
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
                            @include('partials.plan-cta', ['plan' => $plan])
                        </article>
                        @empty
                        <p class="pricing-empty">به‌زودی پلن‌های تعرفه در این بخش نمایش داده می‌شوند.</p>
                        @endforelse
                    </div>

                    <p class="pricing-note">
                        به دنبال حجم بالا هستید؟ <a href="{{ route('sms-packages') }}">بسته‌های پیامکی</a> و پلن سازمانی هم موجود است.
                    </p>
                </div>
            </section>

            <section id="api" class="section alt-bg" aria-labelledby="api-title">
                <div class="container api-shell">
                    <div class="section-heading">
                        <span class="kicker">API &amp; Docs</span>
                        <h2 id="api-title">یکپارچه‌سازی سریع با سیستم‌های شما</h2>
                    </div>

                    <div class="api-box">
                        <div class="code-block">
                            <pre><code>curl -X POST https://api.irnoti.com/v1/sms/send \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "recipient": "09123456789",
    "message": "خوش آمدید، سفارش شما ثبت شد.",
    "sender": "IRNOTI"
  }'</code></pre>
                        </div>
                        <div class="api-points">
                            <div class="api-item">
                                <strong>Authentication</strong>
                                <span>توکن امن و کنترل دسترسی دقیق</span>
                            </div>
                            <div class="api-item">
                                <strong>Webhook</strong>
                                <span>دریافت وضعیت ارسال و تراکنش‌ها در لحظه</span>
                            </div>
                            <div class="api-item">
                                <strong>Docs</strong>
                                <span>مستندات فارسی، کامل و آماده توسعه</span>
                            </div>
                        </div>
                    </div>

                    <div class="section-heading" style="margin-top: 28px; margin-bottom: 0">
                        <a class="heading-link" href="{{ route('docs.index') }}">مطالعه مستندات فنی ←</a>
                    </div>
                </div>
            </section>

            <section id="testimonials" class="section" aria-labelledby="testi-title">
                <div class="container">
                    <div class="section-heading center">
                        <span class="kicker">تجربه مشتریان</span>
                        <h2 id="testi-title">کسب‌وکارها درباره {{ $brand }} چه می‌گویند</h2>
                    </div>

                    <div class="testi-grid">
                        <figure class="testi-card">
                            <blockquote>راه‌اندازی سریع بود و تیم فنی ما در یک بعدازظهر API را به سیستم سفارش‌ها وصل کرد. گزارش‌ها دقیق است.</blockquote>
                            <figcaption>مدیر فنی، فروشگاه اینترنتی</figcaption>
                        </figure>
                        <figure class="testi-card">
                            <blockquote>دفترچه تلفن و ارسال گروهی دقیقاً چیزی بود که برای کمپین‌های فصلی لازم داشتیم؛ بدون دردسر اکسل.</blockquote>
                            <figcaption>مسئول بازاریابی، برند پوشاک</figcaption>
                        </figure>
                        <figure class="testi-card">
                            <blockquote>فاکتور رسمی و کیف پول کار حسابداری ما را ساده کرد. پشتیبانی هم واقعاً پاسخگوست.</blockquote>
                            <figcaption>مدیر مالی، آژانس مسافرتی</figcaption>
                        </figure>
                    </div>
                </div>
            </section>

            <section id="faq" class="section alt-bg faq-section" aria-labelledby="faq-title">
                <div class="container">
                    <div class="section-heading center">
                        <span class="kicker">سوالات متداول</span>
                        <h2 id="faq-title">پاسخ به سوالات رایج درباره سرویس پیامکی {{ $brand }}</h2>
                    </div>

                    <div class="faq-list" itemscope itemtype="https://schema.org/FAQPage">
                        @foreach ($faqs as $i => $faq)
                        <details @if ($i===0) open @endif itemprop="mainEntity" itemscope itemtype="https://schema.org/Question">
                            <summary itemprop="name">{{ $faq->question }}</summary>
                            <div itemprop="acceptedAnswer" itemscope itemtype="https://schema.org/Answer">
                                <p itemprop="text">{{ $faq->answer }}</p>
                            </div>
                        </details>
                        @endforeach
                    </div>

                    <div class="section-more center">
                        <a class="heading-link" href="{{ route('faq') }}">مشاهده همه سوالات متداول ←</a>
                    </div>
                </div>
            </section>

            @if ($latestPosts->isNotEmpty())
            <section id="blog" class="section" aria-labelledby="blog-title">
                <div class="container">
                    <div class="section-heading">
                        <span class="kicker">وبلاگ</span>
                        <h2 id="blog-title">تازه‌ترین مقاله‌های {{ $brand }}</h2>
                        <a class="heading-link" href="{{ route('blog.index') }}">مشاهده همه مقاله‌ها ←</a>
                    </div>

                    <div class="home-blog-grid">
                        @foreach ($latestPosts as $post)
                        <article class="home-blog-card">
                            @if ($post->category)
                            <a class="home-blog-cat" href="{{ route('blog.category', $post->category->slug) }}">{{ $post->category->name }}</a>
                            @endif
                            <h3><a href="{{ route('blog.show', $post->slug) }}">{{ $post->title }}</a></h3>
                            @if ($post->excerpt)
                            <p>{{ \Illuminate\Support\Str::of($post->excerpt)->squish()->limit(120) }}</p>
                            @endif
                            <div class="home-blog-meta">
                                <time datetime="{{ optional($post->published_date)->toDateString() }}">@jdate($post->published_date)</time>
                                <span>·</span>
                                <span>{{ $post->reading_minutes }} دقیقه مطالعه</span>
                            </div>
                        </article>
                        @endforeach
                    </div>
                </div>
            </section>
            @endif

            <section id="about" class="section alt-bg about-band" aria-labelledby="about-title">
                <div class="container two-col">
                    <div class="content-block">
                        <span class="kicker">درباره {{ $brand }}</span>
                        <h2 id="about-title">یک تیم ایرانی، متمرکز بر ارتباط پیامکی کسب‌وکار</h2>
                        <p>
                            {{ $brand }} با هدف ساده‌کردن ارسال پیامک برای فروشگاه‌ها، خدمات و برندها ساخته
                            شده است. تمرکز ما بر پایداری سرویس، شفافیت گزارش‌ها و پشتیبانی واقعی است —
                            از ارسال اولین پیامک تا اتصال کامل سیستم‌های سازمانی.
                        </p>
                        <ul class="check-list">
                            <li>زیرساخت ابری با پایش دائمی</li>
                            <li>احراز هویت و مدارک مطابق مقررات</li>
                            <li>نمادهای اعتماد الکترونیکی و ساماندهی</li>
                        </ul>
                    </div>
                    <div class="info-panel">
                        <div class="info-card about-contact">
                            <h3>در تماس باشیم</h3>
                            <p><a href="tel:{{ $phone }}" dir="ltr">{{ $phoneDisplay }}</a></p>
                            <p><a href="mailto:{{ $email }}" dir="ltr">{{ $email }}</a></p>
                            <a class="btn btn-secondary full" href="{{ route('blog.index') }}">مطالعه وبلاگ</a>
                        </div>
                    </div>
                </div>
            </section>

            <section id="cta" class="section cta-section">
                <div class="container cta-box">
                    <div>
                        <span class="kicker light">امروز شروع کنید</span>
                        <h2>{{ $brand }} را برای رشد کسب‌وکار خود انتخاب کنید</h2>
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