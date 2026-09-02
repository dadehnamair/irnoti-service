@php
    /**
     * Minimal layout for the customer auth screens (docs/starter.md §26/§27):
     * shared header/footer, the runtime theme stylesheet, and the `account`
     * asset bundle. Kept separate from the marketing-page chrome.
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
    <title>@yield('title', 'حساب کاربری') | {{ $brand }}</title>

    <link rel="icon" href="/logo/favicon.png" type="image/png" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800;900&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/css/account.css', 'resources/js/account.js'])
    <link rel="stylesheet" href="{{ route('theme.css') }}" />
</head>

<body>
    <div class="auth-shell">
        @include('partials.site-header')

        <main class="auth-main">
            @yield('content')
        </main>

        @include('partials.site-footer')
    </div>
</body>

</html>
