@extends('layouts.account')

@section('title', 'فیش‌های بانکی')

@section('content')
    @if (session('auth_status'))
        <div class="account-banner is-ok">{{ session('auth_status') }}</div>
    @endif

    <div class="account-card">
        <div class="account-card__head">
            <h2>فیش‌های بانکی من</h2>
            <a class="btn btn-sm btn-primary" href="{{ route('dashboard.receipts.create', ['for' => 'topup']) }}">ثبت فیش جدید</a>
        </div>

        @if (count($receipts) === 0)
            <p class="auth-sub">هنوز فیشی ثبت نکرده‌اید.</p>
        @else
            <div class="account-table-wrap">
                <table class="account-table">
                    <thead>
                        <tr>
                            <th>تاریخ ثبت</th>
                            <th>بابت</th>
                            <th>مبلغ</th>
                            <th>شماره پیگیری</th>
                            <th>تاریخ واریز</th>
                            <th>وضعیت</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($receipts as $receipt)
                            <tr>
                                <td dir="ltr">@jdatetime($receipt->created_at)</td>
                                <td>{{ $receipt->purpose_label }}</td>
                                <td>@toman($receipt->amount) تومان</td>
                                <td dir="ltr">{{ $receipt->tracking_code }}</td>
                                <td dir="ltr">@jdate($receipt->paid_at)</td>
                                <td>
                                    <span @class([
                                        'account-badge',
                                        'is-ok' => $receipt->status === 'approved',
                                        'is-danger' => $receipt->status === 'rejected',
                                        'is-warn' => $receipt->status === 'pending',
                                    ])>{{ $receipt->status_label }}</span>
                                </td>
                            </tr>
                            @if ($receipt->status === 'rejected' && $receipt->admin_note)
                                <tr class="account-table__note">
                                    <td colspan="6">دلیل رد: {{ $receipt->admin_note }}</td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="account-pagination">{{ $receipts->links() }}</div>
        @endif
    </div>
@endsection
