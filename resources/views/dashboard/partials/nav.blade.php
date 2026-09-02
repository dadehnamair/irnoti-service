{{--
    Customer dashboard sidebar (docs/starter.md §15). Grouped into labelled
    sections rendered as a native <details> accordion — the group that owns the
    current page opens by default, the rest stay collapsed. No JS: <summary>
    handles toggle + keyboard. Icons are hand-written inline SVGs.
--}}
@php
    // Which accordion group owns the current route (so it opens on load).
    $navAccount = request()->routeIs('dashboard', 'dashboard.profile*', 'dashboard.plan*', 'subscriptions.*');
    $navSms = request()->routeIs('dashboard.sms*', 'dashboard.lines*', 'dashboard.packages*', 'package-orders.*');
    $navFinance = request()->routeIs('dashboard.wallet', 'wallet.*', 'dashboard.transactions', 'dashboard.invoices*', 'dashboard.receipts*');
    // Fall back to the first group when nothing matched (e.g. a route not in the nav).
    $navAccount = $navAccount || (! $navSms && ! $navFinance);

    $navChevron = '<svg class="account-nav__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>';
@endphp
<nav class="account-nav" aria-label="ناوبری پنل کاربری">
    <details class="account-nav__group" @if ($navAccount) open @endif>
        <summary class="account-nav__label"><span>حساب کاربری</span>{!! $navChevron !!}</summary>
        <div class="account-nav__panel">
            <a href="{{ route('dashboard') }}" @class(['is-active' => request()->routeIs('dashboard')])>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12l9-9 9 9"/><path d="M5 10v10h14V10"/></svg>
                <span>خلاصه حساب</span>
            </a>

            @if (Route::has('dashboard.profile'))
                <a href="{{ route('dashboard.profile') }}" @class(['is-active' => request()->routeIs('dashboard.profile*')])>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/></svg>
                    <span>تکمیل اطلاعات</span>
                </a>
            @endif

            @if (Route::has('dashboard.plans'))
                <a href="{{ route('dashboard.plans') }}" @class(['is-active' => request()->routeIs('dashboard.plan*') || request()->routeIs('subscriptions.*')])>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l2.5 5 5.5.8-4 3.9.9 5.5L12 21l-4.9-2.8.9-5.5-4-3.9L9.5 8z"/></svg>
                    <span>پلن و اشتراک</span>
                </a>
            @endif
        </div>
    </details>

    <details class="account-nav__group" @if ($navSms) open @endif>
        <summary class="account-nav__label"><span>پیامک</span>{!! $navChevron !!}</summary>
        <div class="account-nav__panel">
            @if (Route::has('dashboard.sms'))
                <a href="{{ route('dashboard.sms') }}" @class(['is-active' => request()->routeIs('dashboard.sms*')])>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H8l-5 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    <span>ارسال پیامک</span>
                </a>

                <a href="{{ route('dashboard.sms') }}#senders">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 10h18"/><path d="M8 15h5"/></svg>
                    <span>سرشماره‌ها</span>
                </a>
            @endif

            @if (Route::has('dashboard.lines'))
                <a href="{{ route('dashboard.lines') }}" @class(['is-active' => request()->routeIs('dashboard.lines*')])>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3 19.5 19.5 0 0 1-6-6 19.8 19.8 0 0 1-3-8.6A2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 2 .7 2.9a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.2-1.2a2 2 0 0 1 2.1-.5c.9.3 1.9.6 2.9.7a2 2 0 0 1 1.7 2z"/></svg>
                    <span>خرید خط</span>
                </a>
            @endif

            @if (Route::has('dashboard.packages'))
                <a href="{{ route('dashboard.packages') }}" @class(['is-active' => request()->routeIs('dashboard.packages*') || request()->routeIs('package-orders.*')])>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8l-9-5-9 5 9 5 9-5z"/><path d="M3 8v8l9 5 9-5V8"/><path d="M12 13v8"/></svg>
                    <span>بسته پیامکی</span>
                </a>
            @endif
        </div>
    </details>

    <details class="account-nav__group" @if ($navFinance) open @endif>
        <summary class="account-nav__label"><span>مالی</span>{!! $navChevron !!}</summary>
        <div class="account-nav__panel">
            @if (Route::has('dashboard.wallet'))
                <a href="{{ route('dashboard.wallet') }}" @class(['is-active' => request()->routeIs('dashboard.wallet') || request()->routeIs('wallet.*')])>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7a2 2 0 0 1 2-2h13v4"/><path d="M3 7v10a2 2 0 0 0 2 2h15V7z"/><circle cx="16" cy="13" r="1.4"/></svg>
                    <span>کیف پول</span>
                </a>
            @endif

            @if (Route::has('dashboard.transactions'))
                <a href="{{ route('dashboard.transactions') }}" @class(['is-active' => request()->routeIs('dashboard.transactions')])>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h13l-3-3"/><path d="M20 17H7l3 3"/></svg>
                    <span>سوابق مالی</span>
                </a>
            @endif

            @if (Route::has('dashboard.invoices'))
                <a href="{{ route('dashboard.invoices') }}" @class(['is-active' => request()->routeIs('dashboard.invoices*')])>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2h9l5 5v15H6z"/><path d="M14 2v6h6"/><path d="M9 13h6"/><path d="M9 17h6"/></svg>
                    <span>صورت‌حساب‌ها</span>
                </a>
            @endif

            @if (Route::has('dashboard.receipts'))
                <a href="{{ route('dashboard.receipts') }}" @class(['is-active' => request()->routeIs('dashboard.receipts*')])>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 3v18l3-2 2 2 2-2 2 2 2-2 3 2V3z"/><path d="M9 8h6"/><path d="M9 12h6"/></svg>
                    <span>فیش‌های بانکی</span>
                </a>
            @endif
        </div>
    </details>

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
