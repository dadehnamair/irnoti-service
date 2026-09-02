@php
    $brand = config('theme.brand');
    $seo = config('theme.seo');
    $primary = config('theme.primary');
    $siteUrl = rtrim($seo['url'], '/');

    $metaTitle = trim(($metaTitle ?? $heading ?? 'بلاگ') . ' | ' . $brand);
    $metaDescription = \Illuminate\Support\Str::of($metaDescription ?? $intro ?? $seo['description'])->squish()->limit(160)->value();
    $canonical = $canonical ?? url()->current();
    $ogType = $ogType ?? 'website';
    $ogImage = $ogImage ?? ($siteUrl . $seo['image']);
    $noindex = $noindex ?? false;
@endphp
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="theme-color" content="{{ $primary }}" />

    <title>{{ $metaTitle }}</title>
    <meta name="description" content="{{ $metaDescription }}" />
    @if ($noindex)
        <meta name="robots" content="noindex, follow" />
    @else
        <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1" />
    @endif
    <link rel="canonical" href="{{ $canonical }}" />
    <link rel="alternate" type="application/rss+xml" title="بلاگ {{ $brand }}" href="{{ route('blog.feed') }}" />

    <meta property="og:site_name" content="{{ $brand }}" />
    <meta property="og:locale" content="fa_IR" />
    <meta property="og:type" content="{{ $ogType }}" />
    <meta property="og:title" content="{{ $metaTitle }}" />
    <meta property="og:description" content="{{ $metaDescription }}" />
    <meta property="og:url" content="{{ $canonical }}" />
    <meta property="og:image" content="{{ $ogImage }}" />
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="{{ $metaTitle }}" />
    <meta name="twitter:description" content="{{ $metaDescription }}" />
    <meta name="twitter:image" content="{{ $ogImage }}" />
    @stack('meta')

    <link rel="icon" href="/logo/favicon.png" type="image/png" />

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/css/blog.css', 'resources/js/blog.js'])
    <link rel="stylesheet" href="{{ route('theme.css') }}" />

    @stack('jsonld')
</head>

<body class="blog-body">
    <div class="blog-progress" aria-hidden="true"></div>
    <div class="page-shell">
        @include('partials.site-header')

        <main class="blog-main container">
            @yield('blog')
        </main>

        @include('partials.site-footer')
    </div>
</body>

</html>
