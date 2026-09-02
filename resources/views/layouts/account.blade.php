@php
/**
* Customer account panel layout (docs/starter.md §15 — light version).
* Shared header/footer + a simple sidebar. Uses the `account` asset bundle.
*/
$brand = config('theme.brand');
$primary = config('theme.primary');
@endphp
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="robots" content="noindex, nofollow" />
    <meta name="theme-color" content="{{ $primary }}" />
    <title>@yield('title', 'پنل کاربری') | {{ $brand }}</title>

    <link rel="icon" href="/logo/favicon.png" type="image/png" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800;900&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/css/account.css', 'resources/js/account.js'])
    <link rel="stylesheet" href="{{ route('theme.css') }}" />
</head>

<body>
    <div class="page-shell">
        @include('partials.site-header')

        <main>
            <div class="account-layout">
                <nav class="account-nav" aria-label="ناوبری پنل کاربری">
                    <a href="{{ route('dashboard') }}" @class(['is-active'=> request()->routeIs('dashboard')])>خلاصه حساب</a>
                    @if (Route::has('dashboard.profile'))
                    <a href="{{ route('dashboard.profile') }}" @class(['is-active'=> request()->routeIs('dashboard.profile*')])>تکمیل اطلاعات</a>
                    @endif
                    @if (Route::has('dashboard.plans'))
                    <a href="{{ route('dashboard.plans') }}" @class(['is-active'=> request()->routeIs('dashboard.plan*') || request()->routeIs('subscriptions.*')])>پلن و اشتراک</a>
                    @endif
                    @if (Route::has('dashboard.wallet'))
                    <a href="{{ route('dashboard.wallet') }}" @class(['is-active'=> request()->routeIs('dashboard.wallet') || request()->routeIs('wallet.*')])>کیف پول</a>
                    @endif
                    @if (Route::has('dashboard.transactions'))
                    <a href="{{ route('dashboard.transactions') }}" @class(['is-active'=> request()->routeIs('dashboard.transactions')])>سوابق مالی</a>
                    @endif
                    @if (Route::has('dashboard.packages'))
                    <a href="{{ route('dashboard.packages') }}" @class(['is-active'=> request()->routeIs('dashboard.packages*') || request()->routeIs('package-orders.*')])>بسته پیامکی</a>
                    @endif
                    @if (Route::has('dashboard.invoices'))
                    <a href="{{ route('dashboard.invoices') }}" @class(['is-active'=> request()->routeIs('dashboard.invoices*')])>صورت‌حساب‌ها</a>
                    @endif
                    @if (Route::has('dashboard.receipts'))
                    <a href="{{ route('dashboard.receipts') }}" @class(['is-active'=> request()->routeIs('dashboard.receipts*')])>فیش‌های بانکی</a>
                    @endif
                    @if (Route::has('dashboard.sms'))
                    <a href="{{ route('dashboard.sms') }}" @class(['is-active'=> request()->routeIs('dashboard.sms*')])>ارسال پیامک</a>
                    @endif
                    @if (Route::has('dashboard.lines'))
                    <a href="{{ route('dashboard.lines') }}" @class(['is-active'=> request()->routeIs('dashboard.lines*')])>خرید خط</a>
                    @endif
                    <a href="https://vip.irnoti.com" target="_blank">ورود به پنل VIP</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit">خروج</button>
                    </form>
                </nav>

                <section class="account-panel">
                    @yield('content')
                </section>
            </div>
        </main>

        @include('partials.site-footer')
    </div>
</body>

</html>