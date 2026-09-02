@extends('layouts.account')

@section('title', 'سفارش بسته پیامکی')

@section('content')
    <div class="account-card">
        <h2>سفارش بسته پیامکی</h2>

        @if (session('payment_success'))
            <div class="account-banner is-ok">پرداخت با موفقیت انجام شد و اعتبار پیامکی شما افزایش یافت.</div>
        @endif
        @if (session('auth_status'))
            <div class="account-banner is-ok">{{ session('auth_status') }}</div>
        @endif
        @if (session('payment_error'))
            <div class="account-banner is-warn">{{ session('payment_error') }}</div>
        @endif

        <div class="account-stat-grid">
            <div class="account-stat"><span>بسته</span><strong>{{ $order->package_name }}</strong></div>
            <div class="account-stat"><span>تعداد پیامک</span><strong>@toman($order->sms_count)</strong></div>
            <div class="account-stat"><span>مبلغ</span><strong>{{ (int) $order->price === 0 ? 'رایگان' : toman($order->price) . ' تومان' }}</strong></div>
            <div class="account-stat"><span>وضعیت</span><strong>{{ $order->status_label }}</strong></div>
            <div class="account-stat"><span>کد پیگیری</span><strong dir="ltr">{{ $order->token }}</strong></div>
            @if ($order->paid_at)
                <div class="account-stat"><span>زمان پرداخت</span><strong dir="ltr">@jdatetime($order->paid_at)</strong></div>
            @endif
        </div>

        @if ($order->isPayable())
            <div class="account-actions" style="margin-top:16px">
                @if ($walletBalance >= $order->price)
                    <form method="POST" action="{{ route('package-orders.wallet', $order) }}">
                        @csrf
                        <button type="submit" class="btn btn-primary">پرداخت از کیف پول (@toman($walletBalance) تومان)</button>
                    </form>
                @endif
                @if ($canPayOnline)
                    <a class="btn btn-secondary" href="{{ route('package-orders.pay', $order) }}">پرداخت آنلاین</a>
                @endif
                @if ($receiptEnabled)
                    <a class="btn btn-ghost" href="{{ route('dashboard.receipts.create', ['for' => 'package', 'ref' => $order->token]) }}">ثبت فیش بانکی</a>
                @endif
            </div>
        @endif

        <a class="checkout-back" href="{{ route('dashboard.packages') }}">بازگشت به بسته‌ها</a>
    </div>
@endsection
