@extends('layouts.auth')

@section('title', 'ورود')

@section('content')
    <div class="auth-card">
        <div class="auth-head">
            <span class="kicker">ورود</span>
            <h1>ورود به حساب کاربری</h1>
            <p class="auth-sub">با رمز عبور وارد شوید، یا کد یک‌بارمصرف دریافت کنید.</p>
        </div>

        @if (session('auth_status'))
            <p class="auth-note">{{ session('auth_status') }}</p>
        @endif

        @if (session('auth_error'))
            <div class="auth-errors"><ul><li>{{ session('auth_error') }}</li></ul></div>
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

        <form method="POST" action="{{ route('login.store') }}" class="auth-form">
            @csrf
            <label>
                <span>شماره موبایل</span>
                <input type="tel" name="mobile" value="{{ old('mobile') }}" required
                    inputmode="numeric" dir="ltr" autocomplete="tel" placeholder="09xxxxxxxxx" maxlength="13" />
            </label>
            <label>
                <span>رمز عبور</span>
                <input type="password" name="password" autocomplete="current-password" />
            </label>

            <div class="auth-inline">
                <label><input type="checkbox" name="remember" value="1" /> مرا به خاطر بسپار</label>
            </div>

            <button type="submit" class="btn btn-primary full">ورود با رمز عبور</button>
        </form>

        <form method="POST" action="{{ route('login.otp') }}" class="auth-form">
            @csrf
            <input type="hidden" name="mobile" value="{{ old('mobile') }}" data-mirror-mobile />
            <button type="submit" class="btn btn-ghost full">ورود با کد یک‌بارمصرف</button>
        </form>

        <p class="auth-alt">
            حساب ندارید؟ <a href="{{ route('register') }}">ثبت‌نام</a>
        </p>
    </div>

    <script>
        // Mirror the typed mobile into the "one-time code" form so the user
        // doesn't have to type it twice.
        document.addEventListener('DOMContentLoaded', () => {
            const source = document.querySelector('input[name="mobile"]');
            const mirror = document.querySelector('[data-mirror-mobile]');
            if (source && mirror) {
                source.addEventListener('input', () => { mirror.value = source.value; });
            }
        });
    </script>
@endsection
