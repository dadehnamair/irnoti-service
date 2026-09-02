@extends('layouts.account')

@section('title', 'خلاصه حساب')

@section('content')
    <div class="account-card">
        <h2>خوش آمدید، {{ $user->full_name }}</h2>
        <div class="account-stat-grid">
            <div class="account-stat">
                <span>شماره موبایل</span>
                <strong dir="ltr">{{ $user->mobile }}</strong>
            </div>
            <div class="account-stat">
                <span>وضعیت حساب</span>
                <strong>{{ $user->status_label }}</strong>
            </div>
            <div class="account-stat">
                <span>تکمیل اطلاعات</span>
                <strong>
                    @if ($user->isProfileComplete())
                        <span class="account-badge is-ok">کامل</span>
                    @else
                        <span class="account-badge is-warn">ناقص</span>
                    @endif
                </strong>
            </div>
            <div class="account-stat">
                <span>پلن فعال</span>
                <strong>
                    @if ($user->plan)
                        {{ $user->plan->name }}
                    @else
                        —
                    @endif
                </strong>
            </div>
            @if ($user->plan_expires_at)
                <div class="account-stat">
                    <span>انقضای پلن</span>
                    <strong>{{ $user->plan_expires_at->format('Y/m/d') }}</strong>
                </div>
            @endif
        </div>
    </div>

    @unless ($user->isProfileComplete())
        <div class="account-card">
            <h2>اطلاعات حساب خود را تکمیل کنید</h2>
            <p class="auth-sub">برای استفاده از همهٔ سرویس‌ها، اطلاعات هویتی و آدرس را در چند مرحله وارد کنید.</p>
            @if (Route::has('dashboard.profile'))
                <a class="btn btn-primary" href="{{ route('dashboard.profile') }}">شروع تکمیل اطلاعات</a>
            @endif
        </div>
    @endunless
@endsection
