@extends('layouts.account')

@section('title', $app->name)

@section('content')
    <div class="account-card">
        <div class="mk-app-head" @if ($app->accent_color) style="--mk-accent: {{ $app->accent_color }}" @endif>
            <div class="mk-ico" aria-hidden="true">{!! $app->icon_html !!}</div>
            <h2>{{ $app->name }}</h2>
        </div>

        <div class="account-stat-grid">
            <div class="account-stat"><span>وضعیت</span><strong>{{ $installation->status_label }}</strong></div>
            <div class="account-stat"><span>مبلغ</span><strong>{{ (int) $installation->price === 0 ? 'رایگان' : toman($installation->price) . ' تومان' }}</strong></div>
            @if ($installation->expires_at)
                <div class="account-stat"><span>اعتبار تا</span><strong dir="ltr">@jdate($installation->expires_at)</strong></div>
            @endif
            @if ($installation->last_synced_at)
                <div class="account-stat"><span>آخرین همگام‌سازی</span><strong dir="ltr">@jdatetime($installation->last_synced_at)</strong></div>
            @endif
            <div class="account-stat"><span>کد پیگیری</span><strong dir="ltr">{{ $installation->token }}</strong></div>
        </div>

        @if ($installation->isPayable())
            <div class="account-actions" style="margin-top:16px">
                @if ($walletBalance >= $installation->price)
                    <form method="POST" action="{{ route('marketplace.wallet', $installation) }}">
                        @csrf
                        <button type="submit" class="btn btn-primary">پرداخت از کیف پول (@toman($walletBalance) تومان)</button>
                    </form>
                @endif
                @if ($canPayOnline)
                    <a class="btn btn-secondary" href="{{ route('marketplace.pay', $installation) }}">پرداخت آنلاین</a>
                @endif
                @if ($receiptEnabled)
                    <a class="btn btn-ghost" href="{{ route('dashboard.receipts.create', ['for' => 'marketplace', 'ref' => $installation->token]) }}">ثبت فیش بانکی</a>
                @endif
            </div>
        @endif
    </div>

    @if ($handlerView)
        @include($handlerView, ['installation' => $installation, 'app' => $app])
    @elseif ($installation->isActive())
        <div class="account-card">
            <p class="auth-sub">این افزونه فعال است. امکانات آن از منوی کناری پنل در دسترس است.</p>
        </div>
    @endif

    @if (in_array($installation->status, ['active', 'expired', 'suspended'], true))
        <div class="account-card">
            <h3>حذف افزونه</h3>
            <p class="auth-sub">با حذف افزونه، دسترسی‌ها و همگام‌سازی آن متوقف می‌شود. اطلاعات دریافت‌شده در دفترچه‌تلفن باقی می‌ماند.</p>
            <form method="POST" action="{{ route('marketplace.uninstall', $installation) }}"
                  onsubmit="return confirm('حذف افزونه «{{ $app->name }}»؟')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-ghost">حذف افزونه</button>
            </form>
        </div>
    @endif

    <a class="checkout-back" href="{{ route('dashboard.marketplace') }}">بازگشت به بازارچه</a>
@endsection
