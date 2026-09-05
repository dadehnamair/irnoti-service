@php
    $brand = config('theme.brand');
    $primary = config('theme.primary');
    $email = config('theme.email');
    $phoneDisplay = config('theme.phone_display');

    $metaTitle = 'تکمیل سفارش ' . $bundle->title . ' | ' . $brand;
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
                        <a href="{{ route('lines.group', $group) }}">{{ $group->title }}</a>
                        <span aria-hidden="true">/</span>
                        <span>تکمیل سفارش باندل</span>
                    </nav>

                    <div class="section-heading center">
                        <span class="kicker">تکمیل سفارش</span>
                        <h1>خرید {{ $bundle->title }}</h1>
                        <p>اطلاعات تماس را وارد کنید تا سفارش این باندل ثبت شود.</p>
                    </div>

                    <div class="checkout-layout">
                        <aside class="checkout-summary">
                            <h2>خلاصه باندل</h2>
                            @if ($bundle->description)
                                <p class="checkout-bundle-desc">{{ $bundle->description }}</p>
                            @endif
                            <ul>
                                <li><span>خط</span><strong>{{ $group->title }}</strong></li>
                                @if ($bundle->smsLine)
                                    <li><span>گونهٔ خط</span><strong>{{ $bundle->smsLine->digits }} رقمی{{ $bundle->smsLine->is_rond ? ' — رند' : '' }}</strong></li>
                                @endif
                                @if ($bundle->sms_credit)
                                    <li><span>اعتبار پیامک</span><strong>{{ number_format($bundle->sms_credit) }} عدد</strong></li>
                                @endif
                                @if ($bundle->validity_days)
                                    <li><span>مدت اعتبار</span><strong>{{ number_format($bundle->validity_days) }} روز</strong></li>
                                @endif
                                <li class="checkout-price-row">
                                    <span>مبلغ</span>
                                    <strong>{{ number_format($bundle->price) }} تومان</strong>
                                </li>
                            </ul>

                            @if ($bundle->feature_list)
                                <ul class="checkout-features">
                                    @foreach ($bundle->feature_list as $feature)
                                        <li>{{ $feature }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        </aside>

                        <form method="POST" action="{{ route('lines.order') }}" class="line-order-form checkout-form">
                            @csrf
                            <input type="hidden" name="line_bundle_id" value="{{ $bundle->id }}" />

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
                                <a class="checkout-back" href="{{ route('lines.group', $group) }}">بازگشت به صفحهٔ خط</a>
                            </div>
                        </form>
                    </div>

                    <p class="line-help-contact">نیاز به راهنمایی دارید؟ <a href="tel:{{ config('theme.phone') }}">{{ $phoneDisplay }}</a> — <a href="mailto:{{ $email }}">{{ $email }}</a></p>
                </div>
            </section>
        </main>

        @include('partials.site-footer')
    </div>

    @include('partials.flash')
</body>

</html>
