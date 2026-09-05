@php
    // $jsonLd (BreadcrumbList + ContactPage) comes from ContactController::index().
    $brand = config('theme.brand');
    $seo = config('theme.seo');
    $primary = config('theme.primary');
    $email = config('theme.email');
    $phone = config('theme.phone');
    $phoneDisplay = config('theme.phone_display');
    $address = config('theme.address');
    $url = rtrim($seo['url'], '/');
    $canonical = route('contact');

    $metaTitle = 'تماس با ما | ' . $brand;
    $metaDescription = 'راه‌های ارتباط با تیم ' . $brand . ' — تلفن، ایمیل و فرم تماس آنلاین برای سوالات فروش و پشتیبانی.';
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
                        <span>تماس با ما</span>
                    </nav>

                    <div class="section-heading center">
                        <span class="kicker">تماس با ما</span>
                        <h1>سوالی دارید؟ با ما در تماس باشید</h1>
                        <p>تیم فروش و پشتیبانی {{ $brand }} آماده پاسخ‌گویی به سوالات شماست.</p>
                    </div>

                    <div class="checkout-layout">
                        <aside class="checkout-summary">
                            <h2>راه‌های ارتباطی</h2>
                            <ul class="footer-contact" style="margin-top: 12px;">
                                <li><a href="tel:{{ $phone }}" dir="ltr">{{ $phoneDisplay }}</a></li>
                                <li><a href="mailto:{{ $email }}" dir="ltr">{{ $email }}</a></li>
                                @if ($address)
                                    <li><span>{{ $address }}</span></li>
                                @endif
                            </ul>
                        </aside>

                        <form method="POST" action="{{ route('contact.store') }}" class="line-order-form checkout-form">
                            @csrf

                            <div class="line-order-grid">
                                <label>
                                    <span>نام و نام‌خانوادگی <b>*</b></span>
                                    <input type="text" name="name" value="{{ old('name') }}" required maxlength="100" autofocus @class(['has-error' => $errors->has('name')]) />
                                    @error('name') <span class="field-error">{{ $message }}</span> @enderror
                                </label>
                                <label>
                                    <span>شماره موبایل <b>*</b></span>
                                    <input type="tel" name="mobile" dir="ltr" value="{{ old('mobile') }}" required maxlength="15" inputmode="tel" placeholder="09xxxxxxxxx" @class(['has-error' => $errors->has('mobile')]) />
                                    @error('mobile') <span class="field-error">{{ $message }}</span> @enderror
                                </label>
                                <label>
                                    <span>ایمیل (اختیاری)</span>
                                    <input type="email" name="email" dir="ltr" value="{{ old('email') }}" maxlength="150" @class(['has-error' => $errors->has('email')]) />
                                    @error('email') <span class="field-error">{{ $message }}</span> @enderror
                                </label>
                                <label>
                                    <span>موضوع (اختیاری)</span>
                                    <input type="text" name="subject" value="{{ old('subject') }}" maxlength="150" />
                                </label>
                                <label class="line-order-full">
                                    <span>پیام شما <b>*</b></span>
                                    <textarea name="message" rows="5" required maxlength="2000">{{ old('message') }}</textarea>
                                    @error('message') <span class="field-error">{{ $message }}</span> @enderror
                                </label>
                            </div>

                            <div class="line-order-actions">
                                <button type="submit" class="btn btn-primary full">ارسال پیام</button>
                            </div>
                        </form>
                    </div>
                </div>
            </section>
        </main>

        @include('partials.site-footer')
    </div>

    @include('partials.flash')
</body>

</html>
