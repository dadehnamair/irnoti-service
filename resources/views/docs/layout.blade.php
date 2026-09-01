@php
    $brand = config('theme.brand');
    $seo = config('theme.seo');
    $primary = config('theme.primary');
    $url = rtrim($seo['url'], '/');
    $pageTitle = $pageTitle ?? 'مستندات API';
    $pageDescription = $pageDescription ?? 'مستندات کامل و فارسی API سرویس پیامک ' . $brand . ' — ارسال پیامک، پترن، وضعیت تحویل، دفترچه تلفن و Webhook.';
@endphp
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="theme-color" content="{{ $primary }}" />
    <meta name="description" content="{{ $pageDescription }}" />
    <meta name="robots" content="index, follow" />

    <meta property="og:type" content="article" />
    <meta property="og:site_name" content="{{ $brand }}" />
    <meta property="og:title" content="{{ $pageTitle }} | {{ $brand }}" />
    <meta property="og:description" content="{{ $pageDescription }}" />
    <meta property="og:image" content="{{ $url }}{{ $seo['image'] }}" />

    <link rel="canonical" href="{{ url()->current() }}" />
    <link rel="icon" href="/logo/favicon.png" type="image/png" />

    <title>{{ $pageTitle }} | {{ $brand }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/css/docs.css', 'resources/js/docs.js'])

    <link rel="stylesheet" href="{{ route('theme.css') }}" />
</head>

<body class="docs-body">
    <div class="page-shell">
        <header class="site-header">
            <div class="container nav">
                <a href="{{ route('home') }}" class="brand" aria-label="{{ $brand }}">
                    <img src="/logo/logo-text.png" alt="{{ $brand }}" class="brand-logo" width="260" height="82" />
                </a>

                <nav class="main-nav" aria-label="ناوبری اصلی">
                    <a href="{{ route('home') }}#features">امکانات</a>
                    <a href="{{ route('home') }}#pricing">تعرفه‌ها</a>
                    <a href="{{ route('home') }}#lines">خطوط اختصاصی</a>
                    <a href="{{ route('docs.index') }}" aria-current="page">مستندات API</a>
                </nav>

                <div class="nav-actions">
                    <a class="btn btn-ghost" href="{{ route('home') }}">بازگشت به سایت</a>
                    <a class="btn btn-primary" href="{{ route('home') }}#cta">ثبت‌نام</a>
                </div>

                <button class="menu-toggle" type="button" aria-label="باز کردن منو" aria-expanded="false" data-docs-sidebar-toggle>
                    <span></span><span></span><span></span>
                </button>
            </div>
        </header>

        <div class="docs-shell container">
            <aside class="docs-sidebar" data-docs-sidebar>
                <nav aria-label="فهرست مستندات">
                    @include('docs.partials.nav', ['tree' => $tree, 'activeArticleId' => $article->id ?? null])
                </nav>
            </aside>

            <main class="docs-main">
                @yield('docs')
            </main>
        </div>

        <footer class="site-footer">
            <div class="container footer-bottom">
                <span>© <span>{{ date('Y') }}</span> {{ \Illuminate\Support\Str::of($url)->after('://') }}</span>
                <span>مستندات API</span>
            </div>
        </footer>
    </div>
</body>

</html>
