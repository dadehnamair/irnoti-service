@extends('layouts.account')

@section('title', 'تکمیل سفارش خط')

@section('content')
    <div class="account-card">
        <h2>خرید {{ $line->group_label }}</h2>

        @if ($errors->any())
            <div class="auth-errors">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="account-stat-grid">
            <div class="account-stat">
                <span>شماره</span>
                <strong dir="ltr">{{ $line->display_number }}</strong>
            </div>
            <div class="account-stat">
                <span>نوع خط</span>
                <strong>{{ $line->type_label }}</strong>
            </div>
            <div class="account-stat">
                <span>تعداد ارقام</span>
                <strong>{{ $line->digits }} رقمی</strong>
            </div>
            <div class="account-stat">
                <span>مبلغ</span>
                <strong>
                    @if ($line->requires_inquiry)
                        استعلامی
                    @else
                        {{ number_format($line->price) }} تومان
                    @endif
                </strong>
            </div>
        </div>

        @if ($line->feature_list)
            <ul class="checkout-features" style="margin-top:16px">
                @foreach ($line->feature_list as $feature)
                    <li>{{ $feature }}</li>
                @endforeach
            </ul>
        @endif

        <p class="auth-sub" style="margin-top:16px">
            سفارش با نام <strong>{{ $user->full_name }}</strong> و موبایل <strong dir="ltr">{{ $user->mobile }}</strong> ثبت می‌شود.
        </p>

        <form method="POST" action="{{ route('dashboard.lines.order') }}" class="profile-form" style="margin-top:16px">
            @csrf
            <input type="hidden" name="sms_line_id" value="{{ $line->id }}" />
            <div class="profile-grid">
                <label>
                    <span>شماره دلخواه (اختیاری)</span>
                    <input type="text" name="desired_number" value="{{ old('desired_number') }}" maxlength="40" placeholder="مثلاً 30001234" />
                    @error('desired_number') <span class="field-error">{{ $message }}</span> @enderror
                </label>
                <label class="full">
                    <span>توضیحات</span>
                    <textarea name="note" rows="3" maxlength="1000">{{ old('note') }}</textarea>
                    @error('note') <span class="field-error">{{ $message }}</span> @enderror
                </label>
            </div>

            <div class="profile-actions">
                <a class="btn btn-ghost" href="{{ route('dashboard.lines') }}">بازگشت</a>
                <button type="submit" class="btn btn-primary">
                    {{ $onlinePayment ? 'پرداخت و خرید آنلاین' : 'ثبت سفارش' }}
                </button>
            </div>
        </form>
    </div>
@endsection
