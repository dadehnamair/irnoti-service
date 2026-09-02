@php
    /** Branded error page shell — full public-site header/footer (docs/starter.md §67). */
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
    <title>@yield('code') — @yield('title') | {{ $brand }}</title>

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

        <main>
            <section class="error-page">
                <div class="container">
                    <p class="error-code">@yield('code')</p>
                    <h1 class="error-title">@yield('title')</h1>
                    <p class="error-text">@yield('message')</p>
                    <div class="error-actions">
                        <a class="btn btn-primary" href="{{ route('home') }}">بازگشت به خانه</a>
                        <a class="btn btn-ghost" href="tel:{{ config('theme.phone') }}">تماس با پشتیبانی</a>
                    </div>
                </div>
            </section>
        </main>

        @include('partials.site-footer')
    </div>
</body>

</html>
