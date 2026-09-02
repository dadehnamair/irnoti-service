@extends('layouts.auth')

@section('title', 'تأیید شماره موبایل')

@section('content')
    <div class="auth-card">
        <div class="auth-head">
            <span class="kicker">کد تأیید</span>
            <h1>کد پیامک‌شده را وارد کنید</h1>
            <p class="auth-sub">
                کد ۵ رقمی به شمارهٔ <strong dir="ltr">{{ $mobile }}</strong> ارسال شد.
                <a href="{{ route($purpose === 'login' ? 'login' : 'register') }}">تغییر شماره</a>
            </p>
        </div>

        @if (session('auth_status'))
            <p class="auth-note">{{ session('auth_status') }}</p>
        @endif

        @if (session('otp_debug'))
            <p class="auth-note is-debug">کد تست (فقط محیط توسعه): <strong dir="ltr">{{ session('otp_debug') }}</strong></p>
        @endif

        @if ($errors->any())
            <div class="auth-errors">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('otp.verify') }}" class="auth-form">
            @csrf
            <label>
                <span>کد تأیید</span>
                <input type="text" name="code" class="otp-input" required
                    inputmode="numeric" dir="ltr" autocomplete="one-time-code"
                    maxlength="5" autofocus />
            </label>

            <button type="submit" class="btn btn-primary full">تأیید و ورود</button>
        </form>

        <div class="resend-row">
            <form method="POST" action="{{ route('otp.resend') }}">
                @csrf
                کد را دریافت نکردید؟
                <button type="submit" data-resend="{{ $resendIn }}">ارسال مجدد</button>
                <span data-resend-label></span>
            </form>
        </div>
    </div>
@endsection
