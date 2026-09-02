@extends('layouts.account')

@section('title', 'خرید بسته پیامکی')

@section('content')
    <div class="account-card">
        <h2>خرید بسته «{{ $package->name }}»</h2>


        <div class="account-stat-grid">
            <div class="account-stat">
                <span>تعداد پیامک</span>
                <strong>@toman($package->sms_count)</strong>
            </div>
            <div class="account-stat">
                <span>مبلغ</span>
                <strong>{{ $package->isFree() ? 'رایگان' : toman($package->price) . ' تومان' }}</strong>
            </div>
            <div class="account-stat">
                <span>موجودی کیف پول</span>
                <strong>@toman($walletBalance) تومان</strong>
            </div>
        </div>

        <form method="POST" action="{{ route('package-orders.order') }}" style="margin-top:20px">
            @csrf
            <input type="hidden" name="package" value="{{ $package->slug }}" />

            @unless ($package->isFree())
                <fieldset class="wallet-topup-method">
                    <legend>روش پرداخت</legend>
                    <label>
                        <input type="radio" name="method" value="wallet"
                               @checked(old('method', $walletBalance >= $package->price ? 'wallet' : null) === 'wallet')
                               @disabled($walletBalance < $package->price) />
                        پرداخت از کیف پول
                        @if ($walletBalance < $package->price)<em>(موجودی کافی نیست)</em>@endif
                    </label>
                    <label>
                        <input type="radio" name="method" value="online"
                               @checked(old('method') === 'online') @disabled(! $onlinePayment) />
                        پرداخت آنلاین @unless ($onlinePayment)<em>(غیرفعال)</em>@endunless
                    </label>
                    @if ($receiptEnabled)
                        <label>
                            <input type="radio" name="method" value="receipt" @checked(old('method') === 'receipt') />
                            واریز بانکی و ثبت فیش
                        </label>
                    @endif
                </fieldset>
            @else
                <input type="hidden" name="method" value="wallet" />
            @endunless

            <button type="submit" class="btn btn-primary full">
                {{ $package->isFree() ? 'فعال‌سازی' : 'ثبت و ادامه' }}
            </button>
        </form>

        <a class="checkout-back" href="{{ route('dashboard.packages') }}">بازگشت به بسته‌ها</a>
    </div>
@endsection
