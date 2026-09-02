@extends('layouts.account')

@section('title', 'صورت‌حساب‌ها')

@section('content')
    <div class="account-card">
        <h2>صورت‌حساب‌ها</h2>

        @if (count($invoices) === 0)
            <p class="auth-sub">صورت‌حسابی برای شما صادر نشده است.</p>
        @else
            <div class="account-table-wrap">
                <table class="account-table">
                    <thead>
                        <tr>
                            <th>شماره</th>
                            <th>عنوان</th>
                            <th>مبلغ</th>
                            <th>تاریخ صدور</th>
                            <th>مهلت پرداخت</th>
                            <th>وضعیت</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($invoices as $invoice)
                            <tr>
                                <td dir="ltr">{{ $invoice->number }}</td>
                                <td>{{ $invoice->title }}</td>
                                <td>@toman($invoice->total) تومان</td>
                                <td dir="ltr">@jdate($invoice->issued_at)</td>
                                <td dir="ltr">@jdate($invoice->due_at)</td>
                                <td>
                                    <span @class([
                                        'account-badge',
                                        'is-ok' => $invoice->status === 'paid',
                                        'is-danger' => $invoice->status === 'cancelled',
                                        'is-warn' => in_array($invoice->status, ['issued', 'awaiting_payment']),
                                    ])>{{ $invoice->status_label }}</span>
                                </td>
                                <td><a href="{{ route('dashboard.invoices.show', $invoice) }}">مشاهده</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="account-pagination">{{ $invoices->links() }}</div>
        @endif
    </div>
@endsection
