@extends('layouts.account')

@section('title', 'ارسال پیامک')

@section('content')
    @if (session('sms_status'))
        <div class="account-banner is-ok">{{ session('sms_status') }}</div>
    @endif
    @if (session('sms_error'))
        <div class="account-banner is-danger">{{ session('sms_error') }}</div>
    @endif

    @unless ($hasPanel)
        <div class="account-card">
            <h2>پنل پیامک</h2>
            <p class="auth-sub">
                پنل پیامک شما هنوز فعال نشده است. پس از تأیید حساب، کارشناسان اعتبار اختصاصی شما را
                تنظیم می‌کنند و امکان ارسال پیامک از این بخش فعال می‌شود.
            </p>
        </div>
    @else
        <div class="account-card">
            <h2>اعتبار پنل</h2>

            @if ($creditError)
                <div class="account-banner is-danger">
                    اتصال به پنل ملی‌پیامک برقرار نشد:<br>
                    <span dir="auto">{{ $creditError }}</span>
                </div>
            @endif

            <div class="account-stat-grid">
                <div class="account-stat">
                    <span>اعتبار باقی‌مانده (تعداد پیامک)</span>
                    <strong>
                        @if ($credit !== null)
                            {{ number_format($credit) }} پیامک
                        @else
                            —
                        @endif
                    </strong>
                </div>
                @if (!is_null($creditRial))
                    <div class="account-stat">
                        <span>اعتبار ریالی</span>
                        <strong>{{ number_format($creditRial) }} ریال</strong>
                    </div>
                @endif
                <div class="account-stat">
                    <span>نام کاربری پنل</span>
                    <strong dir="ltr">{{ $user->sms_username }}</strong>
                </div>
                @if ($user->sms_sender)
                    <div class="account-stat">
                        <span>خط فرستنده</span>
                        <strong dir="ltr">{{ $user->sms_sender }}</strong>
                    </div>
                @endif
            </div>
            <div style="margin-top:12px">
                <a class="btn btn-ghost" href="{{ route('dashboard.sms') }}">به‌روزرسانی اعتبار</a>
            </div>
        </div>

        <div class="account-card">
            <h2>ارسال پیامک تکی</h2>

            <form method="POST" action="{{ route('dashboard.sms.send') }}" class="profile-form" data-sms-form>
                @csrf
                <label>
                    <span>شمارهٔ گیرنده *</span>
                    <input type="tel" name="to" value="{{ old('to') }}" dir="ltr" inputmode="tel"
                        placeholder="09121234567" required @class(['has-error' => $errors->has('to')]) />
                    @error('to') <span class="field-error">{{ $message }}</span> @enderror
                </label>
                <label>
                    <span>متن پیام *</span>
                    <textarea name="message" rows="4" maxlength="600" required
                        @class(['has-error' => $errors->has('message')]) data-sms-body>{{ old('message') }}</textarea>
                    <span class="field-hint" data-sms-counter>۰ کاراکتر — ۱ پیامک</span>
                    @error('message') <span class="field-error">{{ $message }}</span> @enderror
                </label>
                <div class="profile-actions">
                    <span></span>
                    <button type="submit" class="btn btn-primary">ارسال پیامک</button>
                </div>
            </form>
        </div>

        <div class="account-card">
            <h2>سابقهٔ ارسال</h2>
            @if ($messages->isEmpty())
                <p class="auth-sub">هنوز پیامکی ارسال نکرده‌اید.</p>
            @else
                <div class="account-table-wrap">
                    <table class="account-table">
                        <thead>
                            <tr>
                                <th>تاریخ</th>
                                <th>گیرنده</th>
                                <th>متن</th>
                                <th>تعداد</th>
                                <th>وضعیت</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($messages as $msg)
                                <tr>
                                    <td>@jdatetime($msg->created_at)</td>
                                    <td dir="ltr">{{ $msg->to }}</td>
                                    <td>{{ \Illuminate\Support\Str::limit($msg->body, 40) }}</td>
                                    <td>{{ $msg->parts }}</td>
                                    <td>
                                        <span class="account-badge {{ $msg->status === 'sent' ? 'is-ok' : ($msg->status === 'failed' ? 'is-danger' : 'is-warn') }}">
                                            {{ $msg->status_label }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endunless
@endsection
