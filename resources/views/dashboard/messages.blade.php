@extends('layouts.account')

@php
    $isSent = $box === 'sent';
    $pageTitle = $isSent ? 'پیام‌های ارسالی' : 'پیام‌های دریافتی';
    // For an incoming message the «فرستنده» is the person who texted in and the
    // «خط» is our سرشماره; for a sent message it is the other way round.
    $peerLabel = $isSent ? 'گیرنده' : 'فرستنده';
    $lineLabel = $isSent ? 'سرشماره' : 'خط دریافت';
@endphp

@section('title', $pageTitle)

@section('content')
    @unless ($hasPanel)
        <div class="account-card">
            <h2>{{ $pageTitle }}</h2>
            <p class="auth-sub">
                پنل پیامک شما هنوز فعال نشده است. پس از تأیید حساب و تنظیم اعتبار اختصاصی،
                {{ $isSent ? 'سابقهٔ پیام‌های ارسالی' : 'پیام‌های دریافتی روی خطوط اختصاصی شما' }}
                در این بخش نمایش داده می‌شود.
            </p>
        </div>
    @else
        <div class="account-card">
            <div class="account-card__head">
                <h2>{{ $pageTitle }}</h2>
                <form method="POST" action="{{ route('dashboard.messages.refresh', $box) }}">
                    @csrf
                    <button type="submit" class="btn btn-ghost">بروزرسانی از {{ sms_provider_label() }}</button>
                </form>
            </div>

            <p class="auth-sub">
                {{ $isSent
                    ? 'فهرست پیام‌های ارسال‌شده از حساب پیامکی شما.'
                    : 'پیام‌هایی که کاربران به سرشماره‌های اختصاصی حساب شما ارسال کرده‌اند.' }}
                @if ($syncedAt)
                    <span class="field-hint">آخرین بروزرسانی: @jdatetime($syncedAt)</span>
                @else
                    <span class="field-hint">در حال دریافت نخستین فهرست از {{ sms_provider_label() }}؛ چند لحظه بعد صفحه را تازه کنید.</span>
                @endif
            </p>

            @if ($messages->isEmpty())
                <p class="auth-sub">
                    @if ($syncedAt)
                        {{ $isSent ? 'پیامک ارسالی‌ای ثبت نشده است.' : 'پیام دریافتی‌ای ثبت نشده است.' }}
                    @else
                        هنوز فهرستی دریافت نشده است.
                    @endif
                </p>
            @else
                <div class="account-table-wrap">
                    <table class="account-table">
                        <thead>
                            <tr>
                                <th>تاریخ</th>
                                <th>{{ $peerLabel }}</th>
                                <th>{{ $lineLabel }}</th>
                                <th>متن</th>
                                @if ($isSent)
                                    <th>تعداد</th>
                                    <th>نتیجه</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($messages as $msg)
                                @php
                                    $peer = $isSent ? $msg->receiver : $msg->sender;
                                    $line = $isSent ? $msg->sender : $msg->receiver;
                                @endphp
                                <tr>
                                    <td>@jdatetime($msg->sent_at)</td>
                                    <td dir="ltr">{{ $peer ? normalize_mobile($peer) : '—' }}</td>
                                    <td dir="ltr">{{ $line ?: '—' }}</td>
                                    <td>{{ \Illuminate\Support\Str::limit($msg->body, 60) }}</td>
                                    @if ($isSent)
                                        <td>{{ $msg->parts }}</td>
                                        <td>
                                            @if ($msg->rec_count > 0)
                                                <span class="account-badge {{ $msg->rec_failed > 0 ? 'is-warn' : 'is-ok' }}">
                                                    {{ number_format($msg->rec_success) }} از {{ number_format($msg->rec_count) }}
                                                </span>
                                            @else
                                                <span class="field-hint">—</span>
                                            @endif
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="account-pagination">{{ $messages->links() }}</div>
            @endif
        </div>
    @endunless
@endsection
