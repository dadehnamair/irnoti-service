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
                <div class="account-side">
                    @include('partials.account-credit-card')
                    @include('dashboard.partials.nav')
                </div>

                <section class="account-panel">
                    @yield('content')
                </section>
            </div>
        </main>

        @include('partials.site-footer')
    </div>

    @include('partials.flash')
</body>

</html>