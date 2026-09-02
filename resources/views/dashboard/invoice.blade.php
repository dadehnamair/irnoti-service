@extends('layouts.account')

@section('title', 'صورت‌حساب ' . $invoice->number)

@section('content')
    <div class="account-card">
        <div class="account-card__head">
            <h2>صورت‌حساب <span dir="ltr">{{ $invoice->number }}</span></h2>
            <span @class([
                'account-badge',
                'is-ok' => $invoice->status === 'paid',
                'is-danger' => $invoice->status === 'cancelled',
                'is-warn' => in_array($invoice->status, ['issued', 'awaiting_payment']),
            ])>{{ $invoice->status_label }}</span>
        </div>


        <p class="auth-sub">{{ $invoice->title }}</p>
        @if ($invoice->description)
            <p class="account-inline-note">{{ $invoice->description }}</p>
        @endif

        <div class="account-table-wrap">
            <table class="account-table">
                <thead>
                    <tr><th>شرح</th><th>تعداد</th><th>قیمت واحد</th><th>مبلغ</th></tr>
                </thead>
                <tbody>
                    @foreach ($invoice->items as $item)
                        <tr>
                            <td>{{ $item->description }}</td>
                            <td>{{ number_format($item->quantity) }}</td>
                            <td>@toman($item->unit_price) تومان</td>
                            <td>@toman($item->amount) تومان</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr><th colspan="3">جمع</th><td>@toman($invoice->subtotal) تومان</td></tr>
                    @if ($invoice->discount)
                        <tr><th colspan="3">تخفیف</th><td>−@toman($invoice->discount) تومان</td></tr>
                    @endif
                    @if ($invoice->tax)
                        <tr><th colspan="3">مالیات</th><td>+@toman($invoice->tax) تومان</td></tr>
                    @endif
                    <tr><th colspan="3">مبلغ قابل پرداخت</th><td><strong>@toman($invoice->total) تومان</strong></td></tr>
                </tfoot>
            </table>
        </div>

        <div class="account-stat-grid" style="margin-top:16px">
            <div class="account-stat"><span>تاریخ صدور</span><strong dir="ltr">@jdate($invoice->issued_at)</strong></div>
            @if ($invoice->due_at)
                <div class="account-stat"><span>مهلت پرداخت</span><strong dir="ltr">@jdate($invoice->due_at)</strong></div>
            @endif
            @if ($invoice->paid_at)
                <div class="account-stat"><span>زمان پرداخت</span><strong dir="ltr">@jdatetime($invoice->paid_at)</strong></div>
            @endif
        </div>

        @if ($invoice->isPayable())
            <div class="account-actions" style="margin-top:16px">
                @if ($walletBalance >= $invoice->total)
                    <form method="POST" action="{{ route('invoices.wallet', $invoice) }}">
                        @csrf
                        <button type="submit" class="btn btn-primary">پرداخت از کیف پول (@toman($walletBalance) تومان)</button>
                    </form>
                @endif
                @if ($canPayOnline)
                    <a class="btn btn-secondary" href="{{ route('invoices.pay', $invoice) }}">پرداخت آنلاین</a>
                @endif
                @if ($receiptEnabled)
                    <a class="btn btn-ghost" href="{{ route('dashboard.receipts.create', ['for' => 'invoice', 'ref' => $invoice->token]) }}">ثبت فیش بانکی</a>
                @endif
            </div>
        @endif

        <a class="checkout-back" href="{{ route('dashboard.invoices') }}">بازگشت به صورت‌حساب‌ها</a>
    </div>
@endsection
