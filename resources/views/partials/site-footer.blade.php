@php
/** Shared public-site footer. Self-contained: reads config('theme.*'). docs/starter.md §67/§81 */
$brand = config('theme.brand');
$email = config('theme.email');
$phone = config('theme.phone');
$phoneDisplay = config('theme.phone_display');
$address = config('theme.address');
$social = array_filter((array) config('theme.social', []));
$siteUrl = rtrim(config('theme.seo.url'), '/');
$about = config('theme.footer.about', config('theme.tagline'));
$columns = (array) config('theme.footer.columns', []);
$trust = (array) config('theme.trust', []);
$onHome = request()->routeIs('home');
$home = url('/');
// «#anchor» links only work on the landing page — rewrite them to /#anchor elsewhere.
$resolve = function (string $href) use ($onHome, $home) {
    if (\Illuminate\Support\Str::startsWith($href, '#')) {
        return $onHome ? $href : $home . '/' . $href;
    }
    return \Illuminate\Support\Str::startsWith($href, ['http://', 'https://', 'mailto:', 'tel:'])
        ? $href
        : url($href);
};
$socialLabels = ['instagram' => 'اینستاگرام', 'telegram' => 'تلگرام', 'linkedin' => 'لینکدین', 'x' => 'X', 'twitter' => 'توییتر', 'whatsapp' => 'واتساپ', 'aparat' => 'آپارات'];
@endphp

<footer class="site-footer">
    <div class="container footer-top">
        <div class="footer-about">
            <a href="{{ $onHome ? '#top' : $home }}" class="brand footer-brand">
                <img src="/logo/logo-text.png" alt="{{ $brand }}" class="brand-logo" width="260" height="82" />
            </a>
            <p class="footer-about-text">{{ $about }}</p>

            <ul class="footer-contact">
                <li>
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.9.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                    <a href="tel:{{ $phone }}" dir="ltr">{{ $phoneDisplay }}</a>
                </li>
                <li>
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                    <a href="mailto:{{ $email }}" dir="ltr">{{ $email }}</a>
                </li>
                @if ($address)
                <li>
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    <span>{{ $address }}</span>
                </li>
                @endif
            </ul>

            @if ($social)
            <div class="footer-social">
                @foreach ($social as $label => $href)
                <a href="{{ $href }}" target="_blank" rel="noopener" aria-label="{{ $socialLabels[$label] ?? ucfirst($label) }}">{{ $socialLabels[$label] ?? ucfirst($label) }}</a>
                @endforeach
            </div>
            @endif
        </div>

        <div class="footer-nav">
            @foreach ($columns as $column)
            <nav class="footer-col" aria-label="{{ $column['title'] ?? '' }}">
                <h3>{{ $column['title'] ?? '' }}</h3>
                <ul>
                    @foreach (($column['links'] ?? []) as $link)
                    <li><a href="{{ $resolve($link['href']) }}">{{ $link['label'] }}</a></li>
                    @endforeach
                </ul>
            </nav>
            @endforeach
        </div>

        @if ($trust)
        <div class="footer-trust" aria-label="نمادهای اعتماد">
            <h3>نمادهای اعتماد</h3>
            <div class="footer-trust-badges">
                @foreach ($trust as $badge)
                    @if (!empty($badge['html']))
                        <div class="trust-badge">{!! $badge['html'] !!}</div>
                    @else
                        <a class="trust-badge" href="{{ $badge['href'] ?? '#' }}" target="_blank" rel="noopener" title="{{ $badge['label'] ?? '' }}">
                            <img src="{{ $badge['image'] }}" alt="{{ $badge['label'] ?? '' }}" width="130" height="150" loading="lazy" />
                        </a>
                    @endif
                @endforeach
            </div>
        </div>
        @endif
    </div>

    <div class="container footer-bottom">
        <span>© <span id="year">{{ date('Y') }}</span> {{ \Illuminate\Support\Str::of($siteUrl)->after('://') }} — تمامی حقوق محفوظ است.</span>
        <span class="footer-bottom-links">
            <a href="{{ url('/terms') }}">قوانین و مقررات</a>
            <a href="{{ url('/privacy') }}">حریم خصوصی</a>
        </span>
    </div>
</footer>
