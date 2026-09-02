{{--
    Customer dashboard sidebar (docs/starter.md §15). The whole menu is now
    catalogue-driven: every entry is a `features` row (App\Support\FeatureCatalog),
    grouped and ordered by that catalogue and rendered by `nav-features`. Built-in
    pages show as real links; not-yet-built items show disabled as «بزودی».
--}}
@php
    $navChevron = '<svg class="account-nav__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>';
@endphp
<nav class="account-nav" aria-label="ناوبری پنل کاربری">
    @include('dashboard.partials.nav-features')

    <div class="account-nav__group account-nav__group--footer">
        <a href="https://vip.irnoti.com" target="_blank" rel="noopener">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3h7v7"/><path d="M10 14L21 3"/><path d="M21 14v7H3V3h7"/></svg>
            <span>ورود به پنل VIP</span>
        </a>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>
                <span>خروج</span>
            </button>
        </form>
    </div>
</nav>
