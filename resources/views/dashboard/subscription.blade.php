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
                    <strong>@jdate($subscription->expires_at)</strong>
                </div>
            @endif
            @if ($subscription->reference_id)
                <div class="account-stat">
                    <span>کد پیگیری بانک</span>
                    <strong dir="ltr">{{ $subscription->reference_id }}</strong>
                </div>
            @endif
        </div>

        @if ($subscription->isPayable())
            <div class="account-actions" style="margin-top:16px">
                @if ($walletBalance >= $subscription->price)
                    <form method="POST" action="{{ route('subscriptions.wallet', $subscription) }}">
                        @csrf
                        <button type="submit" class="btn btn-primary">پرداخت از کیف پول (@toman($walletBalance) تومان)</button>
                    </form>
                @endif
                @if ($canPayOnline)
                    <a class="btn btn-secondary" href="{{ route('subscriptions.pay', $subscription) }}">پرداخت آنلاین</a>
                @endif
                @if ($receiptEnabled)
                    <a class="btn btn-ghost" href="{{ route('dashboard.receipts.create', ['for' => 'plan', 'ref' => $subscription->token]) }}">ثبت فیش بانکی</a>
                @endif
            </div>
        @endif

        <a class="checkout-back" href="{{ route('dashboard') }}">بازگشت به پنل</a>
    </div>
@endsection
