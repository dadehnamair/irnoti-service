@php
    $brand = config('theme.brand');
    $seo = config('theme.seo');
    $primary = config('theme.primary');
    $email = config('theme.email');
    $phoneDisplay = config('theme.phone_display');

    $metaTitle = 'تکمیل سفارش خط ' . $line->prefix . ' | ' . $brand;
@endphp
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="robots" content="noindex, nofollow" />
    <meta name="theme-color" content="{{ $primary }}" />
    <title>{{ $metaTitle }}</title>

    <link rel="icon" href="/logo/favicon.png" type="image/png" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800;900&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ route('theme.css') }}" />
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
                        <span>تکمیل سفارش</span>
                    </nav>

                    <div class="section-heading center">
                        <span class="kicker">تکمیل سفارش</span>
                        <h1>خرید {{ $line->group_label }}</h1>
                        <p>اطلاعات تماس را وارد کنید تا سفارش این خط ثبت شود.</p>
                    </div>

                    <div class="checkout-layout">
                        <aside class="checkout-summary">
                            <h2>خلاصه خط</h2>
                            <div class="checkout-num" dir="ltr">{{ $line->display_number }}</div>
                            <ul>
                                <li><span>پیش‌شماره</span><strong>{{ $line->prefix }}</strong></li>
                                <li><span>تعداد ارقام</span><strong>{{ $line->digits }} رقمی</strong></li>
                                <li><span>نوع خط</span><strong>{{ $line->type_label }}</strong></li>
                                @if ($line->operator)
                                    <li><span>اپراتور</span><strong>{{ $line->operator }}</strong></li>
                                @endif
                                @if ($line->is_rond)
                                    <li><span>شماره</span><strong>رند</strong></li>
                                @endif
                                <li class="checkout-price-row">
                                    <span>مبلغ</span>
                                    <strong>
                                        @if ($line->requires_inquiry)
                                            استعلامی
                                        @else
                                            {{ number_format($line->price) }} تومان
                                        @endif
                                    </strong>
                                </li>
                            </ul>

                            @if ($line->feature_list)
                                <ul class="checkout-features">
                                    @foreach ($line->feature_list as $feature)
                                        <li>{{ $feature }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        </aside>

                        <form method="POST" action="{{ route('lines.order') }}" class="line-order-form checkout-form">
                            @csrf
                            <input type="hidden" name="sms_line_id" value="{{ $line->id }}" />

                            @if ($errors->any())
                                <div class="line-order-errors">
                                    <ul>
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <div class="line-order-grid">
                                <label>
                                    <span>نام و نام خانوادگی <b>*</b></span>
                                    <input type="text" name="customer_name" value="{{ old('customer_name') }}" required maxlength="120" autofocus @class(['has-error' => $errors->has('customer_name')]) />
                                    @error('customer_name') <span class="field-error">{{ $message }}</span> @enderror
                                </label>
                                <label>
                                    <span>شماره موبایل <b>*</b></span>
                                    <input type="tel" name="customer_phone" value="{{ old('customer_phone') }}" required maxlength="20" inputmode="tel" placeholder="09xxxxxxxxx" @class(['has-error' => $errors->has('customer_phone')]) />
                                    @error('customer_phone') <span class="field-error">{{ $message }}</span> @enderror
                                </label>
                                <label>
                                    <span>ایمیل</span>
                                    <input type="email" name="customer_email" value="{{ old('customer_email') }}" maxlength="160" @class(['has-error' => $errors->has('customer_email')]) />
                                    @error('customer_email') <span class="field-error">{{ $message }}</span> @enderror
                                </label>
                                <label>
                                    <span>نام کسب‌وکار</span>
                                    <input type="text" name="company" value="{{ old('company') }}" maxlength="160" />
                                </label>
                                <label>
                                    <span>شماره دلخواه (اختیاری)</span>
                                    <input type="text" name="desired_number" value="{{ old('desired_number') }}" maxlength="40" placeholder="مثلاً 30001234" />
                                </label>
                                <label class="line-order-full">
                                    <span>توضیحات</span>
                                    <textarea name="note" rows="3" maxlength="1000">{{ old('note') }}</textarea>
                                </label>
                            </div>

                            <div class="line-order-actions">
                                <button type="submit" class="btn btn-primary full">
                                    @if ($onlinePayment)
                                        پرداخت و خرید آنلاین
                                    @else
                                        ثبت سفارش
                                    @endif
                                </button>
                                <p>
                                    @if ($onlinePayment)
                                        پس از ثبت، به درگاه پرداخت منتقل می‌شوید و کد پیگیری دریافت می‌کنید.
                                    @else
                                        پس از ثبت، کد پیگیری دریافت می‌کنید و کارشناسان برای هماهنگی پرداخت با شما تماس می‌گیرند.
                                    @endif
                                </p>
                                <a class="checkout-back" href="{{ route('lines') }}">بازگشت به فهرست خطوط</a>
                            </div>
                        </form>
                    </div>

                    <p class="line-help-contact">نیاز به راهنمایی دارید؟ <a href="tel:{{ config('theme.phone') }}">{{ $phoneDisplay }}</a> — <a href="mailto:{{ $email }}">{{ $email }}</a></p>
                </div>
            </section>
        </main>

        @include('partials.site-footer')
    </div>
</body>

</html>
