@extends('layouts.account')

@section('title', 'وضعیت اشتراک')

@section('content')
    <div class="account-card">
        <h2>وضعیت اشتراک</h2>

        @if (session('payment_success'))
            <p class="auth-note">پرداخت با موفقیت انجام شد و پلن شما فعال است.</p>
        @endif
        @if (session('auth_status'))
            <p class="auth-note">{{ session('auth_status') }}</p>
        @endif
        @if (session('payment_error'))
            <div class="auth-errors"><ul><li>{{ session('payment_error') }}</li></ul></div>
        @endif

        <div class="account-stat-grid">
            <div class="account-stat">
                <span>پلن</span>
                <strong>{{ $subscription->plan_name }}</strong>
            </div>
            <div class="account-stat">
                <span>دوره</span>
                <strong>{{ $subscription->billing_period_label }}</strong>
            </div>
            <div class="account-stat">
                <span>مبلغ</span>
                <strong>{{ (int) $subscription->price === 0 ? 'رایگان' : number_format($subscription->price) . ' تومان' }}</strong>
            </div>
            <div class="account-stat">
                <span>وضعیت</span>
                <strong>{{ $subscription->status_label }}</strong>
            </div>
            @if ($subscription->expires_at)
                <div class="account-stat">
                    <span>اعتبار تا</span>
                    <strong>{{ $subscription->expires_at->format('Y/m/d') }}</strong>
                </div>
            @endif
            @if ($subscription->reference_id)
                <div class="account-stat">
                    <span>کد پیگیری بانک</span>
                    <strong dir="ltr">{{ $subscription->reference_id }}</strong>
                </div>
            @endif
        </div>

        @if ($canPayOnline)
            <a class="btn btn-primary" style="margin-top:16px" href="{{ route('subscriptions.pay', $subscription) }}">پرداخت</a>
        @endif

        <a class="checkout-back" href="{{ route('dashboard') }}">بازگشت به پنل</a>
    </div>
@endsection
