@extends('layouts.account')

@section('title', 'خرید خط')

@section('content')

    @if ($myOrders->isNotEmpty())
        <div class="account-card">
            <h2>سفارش‌های خط من</h2>
            <ul class="account-list">
                @foreach ($myOrders as $order)
                    <li>
                        <a href="{{ route('dashboard.lines.show', $order) }}">{{ $order->line_label }}</a>
                        <span class="account-badge {{ in_array($order->status, ['completed', 'paid']) ? 'is-ok' : 'is-warn' }}">
                            {{ $order->status_label }}
                        </span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="account-card">
        <h2>خطوط اختصاصی</h2>
        <p class="auth-sub">یک خط را انتخاب کنید تا سفارش آن با اطلاعات حساب شما ثبت شود.</p>

        @forelse ($groups as $group)
            <h3 class="lines-group-title">{{ $group['label'] }}</h3>
            <div class="account-table-wrap">
                <table class="account-table">
                    <thead>
                        <tr>
                            <th>شماره</th>
                            <th>نوع</th>
                            <th>ارقام</th>
                            <th>قیمت</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($group['lines'] as $line)
                            <tr>
                                <td dir="ltr">{{ $line->display_number }}</td>
                                <td>{{ $line->type_label }}</td>
                                <td>{{ $line->digits }} رقمی</td>
                                <td>
                                    @if ($line->requires_inquiry)
                                        استعلامی
                                    @else
                                        {{ number_format($line->price) }} تومان
                                    @endif
                                </td>
                                <td>
                                    <a class="btn btn-secondary" href="{{ route('dashboard.lines.checkout', $line) }}">انتخاب</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @empty
            <p class="auth-sub">در حال حاضر خطی برای فروش موجود نیست.</p>
        @endforelse
    </div>
@endsection
