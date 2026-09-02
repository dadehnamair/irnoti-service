@extends('layouts.account')

@section('title', $app->name)

@section('content')
    <div class="account-card">
        <h2>{{ $app->name }}</h2>
        @if ($app->vendor)
            <p class="plan-card__meta">ارائه‌دهنده: {{ $app->vendor }}</p>
        @endif

        @if ($app->description)
            <div class="prose">{!! \Illuminate\Support\Str::markdown($app->description, ['html_input' => 'strip']) !!}</div>
        @endif

        <div class="account-stat-grid" style="margin-top:16px">
            <div class="account-stat"><span>دسته</span><strong>{{ $app->category_label }}</strong></div>
            <div class="account-stat"><span>نوع پرداخت</span><strong>{{ $app->billing_type_label }}</strong></div>
            <div class="account-stat"><span>مبلغ</span><strong>{{ $app->price_label }}</strong></div>
            <div class="account-stat"><span>موجودی کیف پول</span><strong>@toman($walletBalance) تومان</strong></div>
        </div>
    </div>

    <div class="account-card">
        <h3>{{ $app->isFree() ? 'افزودن افزونه' : 'خرید و اتصال' }}</h3>

        <form method="POST" action="{{ route('marketplace.install', $app) }}" style="margin-top:12px">
            @csrf

            @foreach ($app->configFields() as $field)
                @php $name = 'config[' . $field['key'] . ']'; @endphp
                <label class="form-row">
                    <span>{{ $field['label'] ?? $field['key'] }}@if (! empty($field['required'])) <em>*</em>@endif</span>
                    @if (($field['type'] ?? 'text') === 'textarea')
                        <textarea name="{{ $name }}" rows="3" @if (! empty($field['required'])) required @endif>{{ old('config.' . $field['key']) }}</textarea>
                    @else
                        <input
                            type="{{ ! empty($field['secret']) ? 'password' : (($field['type'] ?? 'text') === 'number' ? 'number' : 'text') }}"
                            name="{{ $name }}"
                            value="{{ old('config.' . $field['key']) }}"
                            autocomplete="off"
                            @if (! empty($field['required'])) required @endif />
                    @endif
                    @if (! empty($field['help']))
                        <small class="auth-sub">{{ $field['help'] }}</small>
                    @endif
                </label>
            @endforeach

            @unless ($app->isFree())
                <fieldset class="wallet-topup-method">
                    <legend>روش پرداخت</legend>
                    <label>
                        <input type="radio" name="method" value="wallet"
                               @checked(old('method', $walletBalance >= $app->price ? 'wallet' : null) === 'wallet')
                               @disabled($walletBalance < $app->price) />
                        پرداخت از کیف پول
                        @if ($walletBalance < $app->price)<em>(موجودی کافی نیست)</em>@endif
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
                {{ $app->isFree() ? 'افزودن افزونه' : 'ثبت و ادامه' }}
            </button>
        </form>

        <a class="checkout-back" href="{{ route('dashboard.marketplace') }}">بازگشت به بازارچه</a>
    </div>
@endsection
