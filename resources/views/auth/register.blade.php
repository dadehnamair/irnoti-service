@extends('layouts.auth')

@section('title', 'ثبت‌نام')

@section('content')
    <div class="auth-card">
        <div class="auth-head">
            <span class="kicker">ثبت‌نام</span>
            <h1>ساخت حساب کاربری</h1>
            <p class="auth-sub">
                شماره موبایل خود را وارد کنید؛ یک کد تأیید برایتان پیامک می‌شود.
                بقیهٔ اطلاعات را بعد از ورود تکمیل می‌کنید.
            </p>
        </div>

        @if ($intendedPlan)
            <p class="auth-note">
                پس از تأیید شماره، برای فعال‌سازی پلن انتخابی به مرحلهٔ پرداخت هدایت می‌شوید.
            </p>
        @endif

        @if (session('auth_status'))
            <p class="auth-note">{{ session('auth_status') }}</p>
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

        <form method="POST" action="{{ route('register.store') }}" class="auth-form">
            @csrf
            <label>
                <span>شماره موبایل</span>
                <input type="tel" name="mobile" value="{{ old('mobile') }}" required
                    inputmode="numeric" dir="ltr" autocomplete="tel" placeholder="09xxxxxxxxx"
                    maxlength="13" autofocus @class(['has-error' => $errors->has('mobile')]) />
                @error('mobile') <span class="field-error">{{ $message }}</span> @enderror
            </label>

            <button type="submit" class="btn btn-primary full">دریافت کد تأیید</button>
        </form>

        <p class="auth-alt">
            حساب دارید؟ <a href="{{ route('login') }}">ورود</a>
        </p>
    </div>
@endsection
