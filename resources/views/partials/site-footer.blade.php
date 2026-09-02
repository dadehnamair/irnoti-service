@php
/** Shared public-site footer. Self-contained: reads config('theme.*'). */
$brand = config('theme.brand');
$nav = config('theme.nav');
$email = config('theme.email');
$phone = config('theme.phone');
$phoneDisplay = config('theme.phone_display');
$social = array_filter((array) config('theme.social', []));
$siteUrl = rtrim(config('theme.seo.url'), '/');
$onHome = request()->routeIs('home');
$home = url('/');
$resolve = fn (string $href) => \Illuminate\Support\Str::startsWith($href, '#') && ! $onHome
? $home . '/' . $href
: $href;
@endphp

<footer class="site-footer">
    <div class="container footer-inner">
        <div>
            <a href="{{ $onHome ? '#top' : $home }}" class="brand footer-brand">
                <img src="/logo/logo-text.png" alt="{{ $brand }}" class="brand-logo" width="260" height="82" />
            </a>
            <p>پلتفرم حرفه‌ای ارسال پیامک برای کسب‌وکارهای مدرن.</p>
            @if ($social)
            <div class="footer-social">
                @foreach ($social as $label => $href)
                <a href="{{ $href }}" target="_blank" rel="noopener">{{ ucfirst($label) }}</a>
                @endforeach
            </div>
            @endif
        </div>

        <!-- <nav class="footer-links" aria-label="پیوندهای فوتر">
            @foreach ($nav as $item)
                <a href="{{ $resolve($item['href']) }}">{{ $item['label'] }}</a>
            @endforeach
        </nav> -->

        <div class="footer-contact">
            <a href="mailto:{{ $email }}">{{ $email }}</a>
            <a href="tel:{{ $phone }}">{{ $phoneDisplay }}</a>
            @if (config('theme.address'))
            <span class="footer-address">{{ config('theme.address') }}</span>
            @endif
        </div>
    </div>
    <div class="container footer-bottom">
        <span>© <span id="year">{{ date('Y') }}</span> {{ \Illuminate\Support\Str::of($siteUrl)->after('://') }}</span>
        <span>تمامی حقوق محفوظ است.</span>
    </div>
</footer>