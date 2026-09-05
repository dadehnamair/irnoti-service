@php
    // $tiers and $jsonLd (BreadcrumbList + ItemList) come from RepresentationController::index().
    $brand = config('theme.brand');
    $seo = config('theme.seo');
    $primary = config('theme.primary');
    $email = config('theme.email');
    $url = rtrim($seo['url'], '/');
    $canonical = route('representation');

    $metaTitle = 'نمایندگی فروش ' . $brand . ' | همکاری در فروش پنل پیامک';
    $metaDescription = 'با اخذ نمایندگی فروش ' . $brand . ' در کنار ما درآمد کسب کنید — تعرفه‌های همکاری، درصد کمیسیون و شرایط را ببینید و درخواست دهید.';
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
                        <span>نمایندگی فروش</span>
                    </nav>

                    <div class="section-heading center">
                        <span class="kicker">نمایندگی فروش</span>
                        <h1>در کنار {{ $brand }} کسب‌وکار خودتان را بسازید</h1>
                        <p>با معرفی سرویس پیامکی {{ $brand }} به کسب‌وکارهای اطراف خود، کمیسیون فروش دریافت کنید — بدون نیاز به دانش فنی.</p>
                    </div>

                    <div class="pricing-grid">
                        @forelse ($tiers as $tier)
                            <article class="pricing-card @if ($tier->is_featured) featured @endif">
                                <div class="pricing-header">
                                    <h3>{{ $tier->name }}</h3>
                                    @if ($tier->is_featured)
                                        <span class="pill">پیشنهادی</span>
                                    @endif
                                </div>
                                @if ($tier->tagline)
                                    <p class="plan-desc">{{ $tier->tagline }}</p>
                                @endif
                                @if ($tier->description)
                                    <p class="plan-desc">{{ $tier->description }}</p>
                                @endif

                                <div class="price-wrap">
                                    <span class="price">{{ $tier->commission_percent }}٪</span>
                                    <span class="period">کمیسیون</span>
                                </div>
                                <p class="pricing-note" style="margin: 0 0 12px;">سرمایه لازم: {{ $tier->investment_label }}</p>

                                @if ($tier->benefit_list)
                                    <ul>
                                        @foreach ($tier->benefit_list as $benefit)
                                            <li>{{ $benefit }}</li>
                                        @endforeach
                                    </ul>
                                @endif

                                @if ($tier->requirements)
                                    <p class="plan-desc"><strong>شرایط:</strong> {{ $tier->requirements }}</p>
                                @endif

                                <a class="btn btn-primary full" href="#apply">درخواست این تعرفه</a>
                            </article>
                        @empty
                            <p class="pricing-empty">به‌زودی تعرفه‌های نمایندگی فروش در این بخش نمایش داده می‌شوند.</p>
                        @endforelse
                    </div>
                </div>
            </section>

            <section id="apply" class="section alt-bg" aria-labelledby="apply-title">
                <div class="container" style="max-width: 640px;">
                    <div class="section-heading center">
                        <span class="kicker">درخواست نمایندگی</span>
                        <h2 id="apply-title">فرم درخواست نمایندگی فروش</h2>
                        <p>اطلاعات خود را وارد کنید؛ همکاران ما پس از بررسی با شما تماس می‌گیرند.</p>
                    </div>

                    <form method="POST" action="{{ route('representation.apply') }}" class="line-order-form checkout-form">
                        @csrf

                        <div class="line-order-grid">
                            <label>
                                <span>نام و نام‌خانوادگی <b>*</b></span>
                                <input type="text" name="full_name" value="{{ old('full_name') }}" required maxlength="150" @class(['has-error' => $errors->has('full_name')]) />
                                @error('full_name') <span class="field-error">{{ $message }}</span> @enderror
                            </label>
                            <label>
                                <span>شماره موبایل <b>*</b></span>
                                <input type="tel" name="mobile" dir="ltr" value="{{ old('mobile') }}" required maxlength="15" inputmode="tel" placeholder="09xxxxxxxxx" @class(['has-error' => $errors->has('mobile')]) />
                                @error('mobile') <span class="field-error">{{ $message }}</span> @enderror
                            </label>
                            <label>
                                <span>ایمیل (اختیاری)</span>
                                <input type="email" name="email" dir="ltr" value="{{ old('email') }}" maxlength="150" />
                                @error('email') <span class="field-error">{{ $message }}</span> @enderror
                            </label>
                            <label>
                                <span>شهر (اختیاری)</span>
                                <input type="text" name="city" value="{{ old('city') }}" maxlength="100" />
                            </label>
                            <label>
                                <span>نام شرکت (اختیاری)</span>
                                <input type="text" name="company_name" value="{{ old('company_name') }}" maxlength="150" />
                            </label>
                            @if ($tiers->isNotEmpty())
                                <label>
                                    <span>تعرفه مدنظر (اختیاری)</span>
                                    <select name="representation_tier_id">
                                        <option value="">— انتخاب کنید —</option>
                                        @foreach ($tiers as $tier)
                                            <option value="{{ $tier->id }}" @selected((string) old('representation_tier_id') === (string) $tier->id)>{{ $tier->name }}</option>
                                        @endforeach
                                    </select>
                                </label>
                            @endif
                            <label class="line-order-full">
                                <span>توضیحات (اختیاری)</span>
                                <textarea name="message" rows="4" maxlength="2000">{{ old('message') }}</textarea>
                            </label>
                        </div>

                        <div class="line-order-actions">
                            <button type="submit" class="btn btn-primary full">ثبت درخواست نمایندگی</button>
                        </div>
                    </form>
                </div>
            </section>

            <section id="cta" class="section cta-section">
                <div class="container cta-box">
                    <div>
                        <span class="kicker light">سوالی دارید؟</span>
                        <h2>پیش از ثبت درخواست با ما مشورت کنید</h2>
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
