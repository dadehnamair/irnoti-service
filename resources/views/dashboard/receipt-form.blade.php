@extends('layouts.account')

@section('title', 'ثبت فیش بانکی')

@php
    $purposeLabels = [
        'topup' => 'شارژ کیف پول',
        'plan' => 'خرید پلن',
        'line' => 'خرید خط',
        'package' => 'خرید بسته پیامکی',
        'invoice' => 'پرداخت صورت‌حساب',
    ];
@endphp

@section('content')
    <div class="account-card">
        <h2>ثبت فیش بانکی — {{ $purposeLabels[$for] ?? 'پرداخت' }}</h2>
        <p class="auth-sub">
            پس از واریز وجه به یکی از حساب‌های زیر، مشخصات فیش را وارد کنید. پرداخت شما پس از
            بررسی و تأیید کارشناسان اعمال می‌شود.
        </p>

        @if ($errors->any())
            <div class="auth-errors">
                <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ route('receipts.store') }}" enctype="multipart/form-data" class="receipt-form">
            @csrf
            <input type="hidden" name="for" value="{{ $for }}" />
            @if ($ref)<input type="hidden" name="ref" value="{{ $ref }}" />@endif

            <label>
                <span>مبلغ واریزی (تومان)</span>
                <input type="text" name="amount" data-money-input
                       value="{{ old('amount', $amount ?: '') }}" @readonly((bool) $ref) required />
            </label>

            <label>
                <span>شماره پیگیری / رهگیری</span>
                <input type="text" name="tracking_code" dir="ltr" value="{{ old('tracking_code') }}" required />
            </label>

            <label>
                <span>نوع انتقال</span>
                <select name="transfer_type" required>
                    @foreach ($transferTypes as $key => $label)
                        <option value="{{ $key }}" @selected(old('transfer_type') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>

            <label>
                <span>تاریخ واریز (شمسی)</span>
                <input type="text" name="paid_at" dir="ltr" placeholder="{{ jalali_date(now()) }}"
                       value="{{ old('paid_at') }}" required />
                <small class="auth-sub">نمونه: {{ jalali_date(now()) }}</small>
            </label>

            @if ($bankAccounts->isNotEmpty())
                <label>
                    <span>واریز به حساب</span>
                    <select name="bank_account_id">
                        <option value="">— انتخاب کنید —</option>
                        @foreach ($bankAccounts as $account)
                            <option value="{{ $account->id }}" @selected((int) old('bank_account_id') === $account->id)>
                                {{ $account->bank_name }} — {{ $account->owner_name }}
                            </option>
                        @endforeach
                    </select>
                </label>
            @endif

            <label>
                <span>تصویر فیش / رسید</span>
                <input type="file" name="image" accept="image/*" required />
            </label>

            <button type="submit" class="btn btn-primary full">ثبت فیش</button>
        </form>
    </div>

    @include('partials.bank-accounts')
@endsection
