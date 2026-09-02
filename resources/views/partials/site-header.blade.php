@php
    /**
     * Shared public-site header. Self-contained: reads config('theme.*') so any
     * page can @include it. In-page anchors (#features …) are rewritten to
     * absolute links back to the home page when rendered off the landing page.
     */
    $brand = config('theme.brand');
    $nav = config('theme.nav');
    $onHome = request()->routeIs('home');
    $home = url('/');

    $resolve = fn (string $href) => \Illuminate\Support\Str::startsWith($href, '#') && ! $onHome
        ? $home . '/' . $href
        : $href;
@endphp

<header class="site-header">
    <div class="container nav">
        <a href="{{ $onHome ? '#top' : $home }}" class="brand" aria-label="{{ $brand }}">
            <img src="/logo/logo-text.png" alt="{{ $brand }}" class="brand-logo" width="260" height="82" />
        </a>

        <nav class="main-nav" aria-label="ناوبری اصلی">
            @foreach ($nav as $item)
                <a href="{{ $resolve($item['href']) }}"
                    @if (! \Illuminate\Support\Str::startsWith($item['href'], '#') && request()->is(trim($item['href'], '/') . '*')) aria-current="page" @endif>{{ $item['label'] }}</a>
            @endforeach
        </nav>

        <div class="nav-actions">
            @auth
                <a class="btn btn-primary" href="{{ route('dashboard') }}">پنل کاربری</a>
                <form method="POST" action="{{ route('logout') }}" class="nav-logout">
                    @csrf
                    <button type="submit" class="btn btn-ghost">خروج</button>
                </form>
            @else
                <a class="btn btn-primary" href="{{ route('register') }}">ثبت‌نام</a>
                <a class="btn btn-ghost" href="{{ route('login') }}">ورود</a>
            @endauth
        </div>

        <button class="menu-toggle" type="button" aria-label="باز کردن منو" aria-expanded="false" aria-controls="main-nav">
            <span></span>
            <span></span>
            <span></span>
        </button>
    </div>
</header>
