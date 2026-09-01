@php
    $brand = config('theme.brand');
    $seo = config('theme.seo');
    $primary = config('theme.primary');
    $email = config('theme.email');
    $phoneDisplay = config('theme.phone_display');

    $steps = ['pending', 'awaiting_payment', 'paid', 'processing', 'completed'];
    $isFailed = in_array($order->status, ['rejected', 'cancelled'], true);
    $currentIndex = array_search($order->status, $steps, true);
    $currentIndex = $currentIndex === false ? -1 : $currentIndex;

    $metaTitle = 'وضعیت سفارش خط | ' . $brand;
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
                        <span>وضعیت سفارش</span>
                    </nav>

                    <div class="section-heading center">
                        <span class="kicker">پیگیری سفارش</span>
                        <h1>
                            @if ($justCreated)
                                سفارش شما ثبت شد
                            @else
                                وضعیت سفارش خط
                            @endif
                        </h1>
                        <p>کد پیگیری: <strong dir="ltr">{{ $order->token }}</strong> — این نشانی را برای پیگیری‌های بعدی ذخیره کنید.</p>
                    </div>

                    <div class="order-card">
                        <div class="order-row">
                            <span>خط</span>
                            <strong>{{ $order->line_label }}</strong>
                        </div>
                        <div class="order-row">
                            <span>مبلغ</span>
                            <strong>{{ $order->price ? number_format($order->price) . ' تومان' : 'استعلام' }}</strong>
                        </div>
                        <div class="order-row">
                            <span>تاریخ ثبت</span>
                            <strong>{{ $order->created_at->format('Y-m-d H:i') }}</strong>
                        </div>
                        <div class="order-row">
                            <span>وضعیت</span>
                            <strong class="order-status @if ($isFailed) is-failed @endif">{{ $order->status_label }}</strong>
                        </div>
                        @if ($order->admin_note)
                            <div class="order-row">
                                <span>توضیح پشتیبانی</span>
                                <strong>{{ $order->admin_note }}</strong>
                            </div>
                        @endif
                    </div>

                    @unless ($isFailed)
                        <ol class="order-progress">
                            @foreach ($steps as $i => $step)
                                <li class="@if ($i < $currentIndex) done @elseif ($i === $currentIndex) current @endif">
                                    {{ \App\Models\LineOrder::STATUSES[$step] }}
                                </li>
                            @endforeach
                        </ol>
                    @endunless

                    <p class="line-help-contact">
                        برای هماهنگی پرداخت و فعال‌سازی با شما تماس می‌گیریم. در صورت نیاز:
                        <a href="tel:{{ config('theme.phone') }}">{{ $phoneDisplay }}</a> —
                        <a href="mailto:{{ $email }}">{{ $email }}</a>
                    </p>

                    <div class="order-actions">
                        <a class="btn btn-secondary" href="{{ route('lines') }}">بازگشت به خطوط</a>
                    </div>
                </div>
            </section>
        </main>

        @include('partials.site-footer')
    </div>
</body>

</html>
