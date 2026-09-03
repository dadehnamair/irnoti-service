@extends('layouts.account')

@section('title', 'ارسال به پیام‌رسان‌ها')

@section('content')
    <div class="account-card">
        <div class="account-card__head">
            <h2>پیام‌رسان‌ها</h2>
        </div>
        <p class="auth-sub">
            ارسال انبوه یک پیام به یک گروه مخاطبین یا فهرستی از شماره‌ها/شناسه‌ها روی پیام‌رسان‌ها.
            هزینهٔ هر ارسال بر اساس تعداد گیرنده و تعرفهٔ همان پیام‌رسان از کیف پول کسر می‌شود و
            بخش ناموفق به‌صورت خودکار برگشت می‌خورد.
        </p>

        <div class="account-stat-grid">
            <div class="account-stat">
                <span>موجودی کیف پول</span>
                <strong>@toman($walletBalance) تومان</strong>
            </div>
        </div>
    </div>

    <div class="account-card">
        <h2>انتخاب پیام‌رسان</h2>

        @if (empty($channels))
            <p class="auth-sub">در حال حاضر هیچ پیام‌رسانی فعال نیست.</p>
        @else
            <div class="sender-list">
                @foreach ($channels as $channel)
                    <a class="sender-option" href="{{ route('dashboard.messenger.create', $channel['key']) }}">
                        <span>{{ $channel['label'] }}</span>
                        <span class="field-hint">
                            @if ($channel['tariff'] > 0)
                                هر گیرنده @toman($channel['tariff']) تومان
                            @else
                                رایگان
                            @endif
                        </span>
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    <div class="account-card">
        <h2>کمپین‌های اخیر</h2>
        @if ($campaigns->isEmpty())
            <p class="auth-sub">هنوز کمپینی ارسال نکرده‌اید.</p>
        @else
            <div class="account-table-wrap">
                <table class="account-table">
                    <thead>
                        <tr>
                            <th>تاریخ</th>
                            <th>پیام‌رسان</th>
                            <th>متن</th>
                            <th>گیرندگان</th>
                            <th>موفق</th>
                            <th>ناموفق</th>
                            <th>هزینه</th>
                            <th>وضعیت</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($campaigns as $campaign)
                            <tr>
                                <td>@jdatetime($campaign->created_at)</td>
                                <td>{{ $campaign->channel_label }}</td>
                                <td>{{ \Illuminate\Support\Str::limit($campaign->body, 40) }}</td>
                                <td>{{ number_format($campaign->recipients_count) }}</td>
                                <td>{{ number_format($campaign->success_count) }}</td>
                                <td>{{ number_format($campaign->failed_count) }}</td>
                                <td>@toman($campaign->cost) تومان</td>
                                <td>
                                    <span class="account-badge {{ $campaign->status === 'sent' ? 'is-ok' : (in_array($campaign->status, ['failed']) ? 'is-danger' : 'is-warn') }}">
                                        {{ $campaign->status_label }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
