@extends('layouts.account')

@php
    $isSent = $box === 'sent';
    $pageTitle = $isSent ? 'پیام‌های ارسالی' : 'پیام‌های دریافتی';
    // For an incoming message the «فرستنده» is the person who texted in and the
    // «گیرنده» is our سرشماره; for a sent message it is the other way round.
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
            <h2>{{ $pageTitle }}</h2>
            <p class="auth-sub">
                {{ $isSent
                    ? 'فهرست پیام‌های ارسال‌شده از حساب پیامکی شما، مستقیم از ' . sms_provider_label() . '.'
                    : 'پیام‌هایی که کاربران به سرشماره‌های اختصاصی حساب شما ارسال کرده‌اند، مستقیم از ' . sms_provider_label() . '.' }}
            </p>

            @if ($error)
                <div class="account-banner is-danger">
                    دریافت فهرست پیام‌ها از {{ sms_provider_label() }} ناموفق بود:<br>
                    <span dir="auto">{{ $error }}</span>
                </div>
            @endif

            @if (empty($messages))
                <p class="auth-sub">
                    @if ($error)
                        در حال حاضر امکان نمایش پیام‌ها نیست؛ کمی بعد دوباره تلاش کنید.
                    @elseif ($page > 1)
                        در این صفحه پیامی یافت نشد.
                    @else
                        {{ $isSent ? 'هنوز پیامکی ارسال نکرده‌اید.' : 'هنوز پیامی دریافت نکرده‌اید.' }}
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
                                    $peer = $isSent ? $msg['receiver'] : $msg['sender'];
                                    $line = $isSent ? $msg['sender'] : $msg['receiver'];
                                @endphp
                                <tr>
                                    <td>@jdatetime($msg['date'])</td>
                                    <td dir="ltr">{{ $peer ? normalize_mobile($peer) : '—' }}</td>
                                    <td dir="ltr">{{ $line ?: '—' }}</td>
                                    <td>{{ \Illuminate\Support\Str::limit($msg['body'], 60) }}</td>
                                    @if ($isSent)
                                        <td>{{ $msg['parts'] }}</td>
                                        <td>
                                            @if ($msg['rec_count'] > 0)
                                                <span class="account-badge {{ $msg['rec_failed'] > 0 ? 'is-warn' : 'is-ok' }}">
                                                    {{ number_format($msg['rec_success']) }} از {{ number_format($msg['rec_count']) }}
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

                @if ($page > 1 || $hasMore)
                    <div class="account-pagination">
                        @if ($page > 1)
                            <a class="btn btn-ghost" href="{{ request()->fullUrlWithQuery(['page' => $page - 1]) }}">صفحهٔ قبل</a>
                        @endif
                        <span class="field-hint">صفحهٔ {{ $page }}</span>
                        @if ($hasMore)
                            <a class="btn btn-ghost" href="{{ request()->fullUrlWithQuery(['page' => $page + 1]) }}">صفحهٔ بعد</a>
                        @endif
                    </div>
                @endif
            @endif
        </div>
    @endunless
@endsection
