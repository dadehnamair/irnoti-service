@php
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

// Pricing plans come from the DB (docs/starter.md §8 / §40) and are managed
// from the Filament admin panel. Both the pricing cards and the Product
// schema below read from this collection.
$plans = \App\Models\Plan::query()->active()->ordered()->get();

// Latest published articles for the blog teaser strip (degrades to empty).
$latestPosts = rescue(
    fn () => \App\Models\BlogPost::query()->published()->with('category')->limit(3)->get(),
    collect(),
    false
);

$lines = ['۱۰۰۰', '۲۰۰۰', '۳۰۰۰', '۵۰۰۰۱', '۵۰۰۰۴', '۰۲۱', '۰۲۶', '۰۴۱', '۰۵۱', '۰۷۱', '۲۱۷۰۰۰', '۹۰۰۰', '۹۹۹۹', '۹۹۸'];

$faqs = [
['q' => 'آیا irnoti برای شروع کسب‌وکارهای کوچک مناسب است؟', 'a' => 'بله، پلن‌های پایه و حرفه‌ای برای کسب‌وکارهای کوچک تا بزرگ طراحی شده‌اند و برای فروشگاه‌ها، خدمات و برندها مناسب هستند.'],
['q' => 'آیا امکان ارسال پیامک زمان‌بندی‌شده وجود دارد؟', 'a' => 'بله، از طریق پنل و API می‌توانید ارسال زمان‌بندی‌شده و کمپین‌های هدفمند داشته باشید.'],
['q' => 'آیا API دارای مستندات فارسی است؟', 'a' => 'بله، مستندات API به‌صورت حرفه‌ای و ساده برای تیم‌های فنی آماده است و به‌راحتی در پروژه‌های مختلف قابل استفاده می‌باشد.'],
];

// JSON-LD graph: Organization + WebSite + one Product per plan.
$graph = [
[
'@type' => 'Organization',
'@id' => $url . '/#organization',
'name' => $brand,
'url' => $url . '/',
'logo' => $url . $seo['image'],
'description' => $seo['description'],
'contactPoint' => [
'@type' => 'ContactPoint',
'contactType' => 'customer support',
'email' => $email,
'telephone' => $phone,
'areaServed' => 'IR',
'availableLanguage' => ['fa'],
],
],
[
'@type' => 'WebSite',
'@id' => $url . '/#website',
'name' => $brand,
'url' => $url . '/',
'inLanguage' => 'fa-IR',
'publisher' => ['@id' => $url . '/#organization'],
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
'price' => $plan->price_monthly * 10, // Toman -> Rial for ISO 4217
'priceCurrency' => 'IRR',
'availability' => 'https://schema.org/InStock',
'url' => $url . '/#pricing',
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
                            راهکار پیامکی برای کسب‌وکارهای مدرن
                        </p>
                        <h1>پنل پیامک حرفه‌ای برای کسب‌وکارها و فروشگاه‌های آنلاین</h1>
                        <p>
                            {{ $brand }} یک سرویس تخصصی ارسال پیامک، پنل پیامکی، خطوط اختصاصی و API
                            قدرتمند برای کسب‌وکارهاست؛ با ارسال سریع، گزارش دقیق، مدیریت مخاطب
                            و پشتیبانی حرفه‌ای برای رشد بهتر فروش و ارتباط با مشتریان.
                        </p>

                        <div class="hero-actions">
                            <a class="btn btn-primary" href="#cta">شروع رایگان</a>
                            <a class="btn btn-secondary" href="#pricing">مشاهده تعرفه‌ها</a>
                        </div>

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
                                    <span>درآمد</span>
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

            <section id="features" class="section" aria-labelledby="features-title">
                <div class="container">
                    <div class="section-heading">
                        <span class="kicker">چرا {{ $brand }}؟</span>
                        <h2 id="features-title">ویژگی‌های اصلی پنل پیامک {{ $brand }} برای کسب‌وکارهای مدرن</h2>
                    </div>

                    <div class="feature-grid">
                        <article class="feature-card">
                            <div class="icon-wrap blue" aria-hidden="true">✉️</div>
                            <h3>ارسال پیامک</h3>
                            <p>ارسال فوری پیامک‌های فردی، گروهی و زمان‌بندی‌شده با کارایی بالا.</p>
                        </article>

                        <article class="feature-card">
                            <div class="icon-wrap purple" aria-hidden="true">📦</div>
                            <h3>ایجاد کمپین</h3>
                            <p>طراحی کمپین‌های فروش، تحویل، یادآوری و اطلاع‌رسانی با تجربه کاربری ساده.</p>
                        </article>

                        <article class="feature-card">
                            <div class="icon-wrap green" aria-hidden="true">🧠</div>
                            <h3>پیامک پترن</h3>
                            <p>ساخت الگوهای پیامکی استاندارد و استفاده مجدد برای ارسال‌های تکرارشونده.</p>
                        </article>

                        <article class="feature-card">
                            <div class="icon-wrap orange" aria-hidden="true">📞</div>
                            <h3>دفترچه مخاطب</h3>
                            <p>مدیریت گروه‌ها، فیلترهای دقیق و واردکردن داده‌ها با سرعت و نظم بالا.</p>
                        </article>

                        <article class="feature-card">
                            <div class="icon-wrap red" aria-hidden="true">⏱️</div>
                            <h3>زمان‌بندی</h3>
                            <p>ارسال در زمان دقیق، هفتگی یا ماهانه بدون نیاز به حضور دستی.</p>
                        </article>

                        <article class="feature-card">
                            <div class="icon-wrap gold" aria-hidden="true">⚡</div>
                            <h3>API حرفه‌ای</h3>
                            <p>اتصال سریع به اپلیکیشن‌ها، CRM و سیستم‌های فروش با مستندات دقیق.</p>
                        </article>
                    </div>
                </div>
            </section>

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
                            <a href="{{ $plan->cta_url ?: '#cta' }}" class="btn {{ $plan->cta_style }} full">{{ $plan->cta_label }}</a>
                        </article>
                        @empty
                        <p class="pricing-empty">به‌زودی پلن‌های تعرفه در این بخش نمایش داده می‌شوند.</p>
                        @endforelse
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
                        <span class="line-tag">خطوط {{ $line }}</span>
                        @endforeach
                    </div>
                </div>
            </section>

            <section id="api" class="section" aria-labelledby="api-title">
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
                </div>
            </section>

            <section id="faq" class="section faq-section" aria-labelledby="faq-title">
                <div class="container">
                    <div class="section-heading center">
                        <span class="kicker">سوالات متداول</span>
                        <h2 id="faq-title">پاسخ به سوالات رایج درباره سرویس پیامکی {{ $brand }}</h2>
                    </div>

                    <div class="faq-list" itemscope itemtype="https://schema.org/FAQPage">
                        @foreach ($faqs as $i => $faq)
                        <details @if ($i===0) open @endif itemprop="mainEntity" itemscope itemtype="https://schema.org/Question">
                            <summary itemprop="name">{{ $faq['q'] }}</summary>
                            <div itemprop="acceptedAnswer" itemscope itemtype="https://schema.org/Answer">
                                <p itemprop="text">{{ $faq['a'] }}</p>
                            </div>
                        </details>
                        @endforeach
                    </div>
                </div>
            </section>

            @if ($latestPosts->isNotEmpty())
            <section id="blog" class="section alt-bg" aria-labelledby="blog-title">
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
                                <time datetime="{{ optional($post->published_date)->toDateString() }}">{{ optional($post->published_date)->format('Y/m/d') }}</time>
                                <span>·</span>
                                <span>{{ $post->reading_minutes }} دقیقه مطالعه</span>
                            </div>
                        </article>
                        @endforeach
                    </div>
                </div>
            </section>
            @endif

            <section id="cta" class="section cta-section">
                <div class="container cta-box">
                    <div>
                        <span class="kicker light">امروز شروع کنید</span>
                        <h2>{{ $brand }} را برای رشد کسب‌وکار خود انتخاب کنید</h2>
                    </div>
                    <div class="cta-actions">
                        <a class="btn btn-primary" href="mailto:{{ $email }}">{{ $email }}</a>
                        <a class="btn btn-secondary light" href="#top">بازگشت به بالا</a>
                    </div>
                </div>
            </section>
        </main>

        @include('partials.site-footer')
    </div>
</body>

</html>