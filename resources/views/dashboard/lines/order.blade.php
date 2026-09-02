@extends('layouts.account')

@section('title', 'وضعیت سفارش خط')

@section('content')
    @if ($justCreated)
        <div class="account-banner is-ok">سفارش شما ثبت شد. کد پیگیری: <strong dir="ltr">{{ $order->token }}</strong></div>
    @endif
    @if (session('payment_error'))
        <div class="account-banner is-danger">{{ session('payment_error') }}</div>
    @endif

    <div class="account-card">
        <h2>{{ $order->line_label }}</h2>

        <div class="account-stat-grid">
            <div class="account-stat">
                <span>کد پیگیری</span>
                <strong dir="ltr">{{ $order->token }}</strong>
            </div>
            <div class="account-stat">
                <span>وضعیت</span>
                <strong>{{ $order->status_label }}</strong>
            </div>
            <div class="account-stat">
                <span>مبلغ</span>
                <strong>{{ number_format($order->price) }} تومان</strong>
            </div>
            @if ($order->paid_at)
                <div class="account-stat">
                    <span>پرداخت</span>
                    <strong>{{ $order->paid_at->format('Y/m/d H:i') }}</strong>
                </div>
            @endif
        </div>

        @if ($order->admin_note)
            <p class="account-inline-note is-info">{{ $order->admin_note }}</p>
        @endif

        <div style="margin-top:16px; display:flex; gap:12px; flex-wrap:wrap">
            @if ($canPayOnline)
                <a class="btn btn-primary" href="{{ route('dashboard.lines.pay', $order) }}">پرداخت آنلاین</a>
            @endif
            <a class="btn btn-ghost" href="{{ route('dashboard.lines') }}">بازگشت به خطوط</a>
        </div>
    </div>
@endsection
