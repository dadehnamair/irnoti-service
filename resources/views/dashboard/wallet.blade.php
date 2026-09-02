@extends('layouts.account')

@section('title', 'کیف پول')

@section('content')

    <div class="account-card">
        <h2>کیف پول</h2>
        <div class="account-stat-grid">
            <div class="account-stat">
                <span>موجودی فعلی</span>
                <strong>@toman($wallet->balance) تومان</strong>
            </div>
            <div class="account-stat">
                <span>اعتبار پیامکی</span>
                <strong>@toman($user->sms_credit) پیامک</strong>
            </div>
        </div>
        <a class="checkout-back" href="{{ route('dashboard.transactions') }}">مشاهدهٔ سوابق مالی</a>
    </div>

    @if ($pendingTopups->isNotEmpty())
        <div class="account-card">
            <h3>شارژهای در انتظار پرداخت</h3>
            <ul class="account-list">
                @foreach ($pendingTopups as $topup)
                    <li>
                        <span>@toman($topup->amount) تومان — @jdatetime($topup->created_at)</span>
                        @if ($onlinePayment)
                            <a class="btn btn-sm btn-primary" href="{{ route('wallet.topup.pay', $topup) }}">ادامهٔ پرداخت</a>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="account-card">
        <h3>شارژ حساب</h3>


        <form method="POST" action="{{ route('dashboard.wallet.topup') }}" class="wallet-topup-form">
            @csrf
            <label>
                <span>مبلغ (تومان)</span>
                <input type="text" name="amount" data-money-input value="{{ old('amount') }}"
                       placeholder="مثلاً {{ number_format($minTopup) }}" required />
            </label>
            <p class="auth-sub">حداقل مبلغ شارژ {{ number_format($minTopup) }} تومان است.</p>

            <fieldset class="wallet-topup-method">
                <legend>روش پرداخت</legend>
                <label>
                    <input type="radio" name="method" value="online" @checked(old('method', 'online') === 'online') @disabled(! $onlinePayment) />
                    پرداخت آنلاین
                    @unless ($onlinePayment)<em>(غیرفعال)</em>@endunless
                </label>
                @if ($receiptEnabled)
                    <label>
                        <input type="radio" name="method" value="receipt" @checked(old('method') === 'receipt') />
                        واریز بانکی و ثبت فیش
                    </label>
                @endif
            </fieldset>

            <button type="submit" class="btn btn-primary full">ادامه</button>
        </form>
    </div>

    @if ($receiptEnabled)
        @include('partials.bank-accounts')
    @endif

    <div class="account-card">
        <div class="account-card__head">
            <h3>آخرین تراکنش‌ها</h3>
            <a href="{{ route('dashboard.transactions') }}">همه</a>
        </div>
        @include('dashboard.partials.transaction-table', ['transactions' => $transactions, 'paginated' => false])
    </div>
@endsection
