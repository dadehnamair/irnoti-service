@extends('layouts.account')

@section('title', 'ارسال پیامک')

@section('content')
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
                    اتصال به {{ sms_provider_label() }} برقرار نشد:<br>
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
                        <span>اعتبار ریالی پنل</span>
                        <strong>@toman(rial_to_toman($creditRial)) تومان</strong>
                    </div>
                @endif
                <div class="account-stat">
                    <span>نام کاربری پنل</span>
                    <strong dir="ltr">{{ $user->sms_username }}</strong>
                </div>
                @if ($defaultSender)
                    <div class="account-stat">
                        <span>سرشمارهٔ پیش‌فرض</span>
                        <strong dir="ltr">{{ $defaultSender }}</strong>
                    </div>
                @endif
            </div>
            <div style="margin-top:12px">
                <a class="btn btn-ghost" href="{{ route('dashboard.sms') }}">به‌روزرسانی اعتبار</a>
            </div>
        </div>

        <div class="account-card" id="senders">
            <h2>سرشماره‌های فرستنده</h2>
            <p class="auth-sub">
                فهرست خطوط اختصاصی حساب ملی‌پیامک شما. یکی را به‌عنوان پیش‌فرض انتخاب کنید؛ هنگام
                ارسال هم می‌توانید سرشمارهٔ دیگری را انتخاب کنید.
            </p>

            @if (empty($numbers))
                <p class="auth-sub">هنوز سرشماره‌ای دریافت نشده است. دکمهٔ زیر را بزنید.</p>
            @else
                <form method="POST" action="{{ route('dashboard.sms.numbers.default') }}" class="profile-form">
                    @csrf
                    <div class="sender-list">
                        @foreach ($numbers as $number)
                            <label class="sender-option">
                                <input type="radio" name="from" value="{{ $number }}"
                                    @checked($number === $defaultSender) />
                                <span dir="ltr">{{ $number }}</span>
                                @if ($number === $defaultSender)
                                    <span class="account-badge is-ok">پیش‌فرض</span>
                                @endif
                            </label>
                        @endforeach
                    </div>
                    @error('from') <span class="field-error">{{ $message }}</span> @enderror
                    <div class="profile-actions">
                        <span></span>
                        <button type="submit" class="btn btn-primary">ذخیره به‌عنوان پیش‌فرض</button>
                    </div>
                </form>
            @endif

            <form method="POST" action="{{ route('dashboard.sms.numbers.refresh') }}" style="margin-top:12px">
                @csrf
                <button type="submit" class="btn btn-ghost">به‌روزرسانی از ملی‌پیامک</button>
                @if ($numbersSyncedAt)
                    <span class="field-hint">آخرین به‌روزرسانی: @jdatetime($numbersSyncedAt)</span>
                @endif
            </form>
        </div>

        <div class="account-card">
            <h2>ارسال پیامک تکی</h2>

            <form method="POST" action="{{ route('dashboard.sms.send') }}" class="profile-form" data-sms-form>
                @csrf
                @if (count($numbers) > 1)
                    <label>
                        <span>سرشماره فرستنده</span>
                        <select name="from" dir="ltr" @class(['has-error' => $errors->has('from')])>
                            @foreach ($numbers as $number)
                                <option value="{{ $number }}"
                                    @selected(old('from', $defaultSender) === $number)>{{ $number }}</option>
                            @endforeach
                        </select>
                        @error('from') <span class="field-error">{{ $message }}</span> @enderror
                    </label>
                @elseif (count($numbers) === 1)
                    <input type="hidden" name="from" value="{{ $numbers[0] }}" />
                @endif
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
                                <th>سرشماره</th>
                                <th>گیرنده</th>
                                <th>متن</th>
                                <th>تعداد</th>
                                <th>وضعیت</th>
                                <th>تحویل</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($messages as $msg)
                                <tr>
                                    <td>@jdatetime($msg->created_at)</td>
                                    <td dir="ltr">{{ $msg->from ?: '—' }}</td>
                                    <td dir="ltr">{{ $msg->to }}</td>
                                    <td>{{ \Illuminate\Support\Str::limit($msg->body, 40) }}</td>
                                    <td>{{ $msg->parts }}</td>
                                    <td>
                                        <span class="account-badge {{ $msg->status === 'sent' ? 'is-ok' : ($msg->status === 'failed' ? 'is-danger' : 'is-warn') }}">
                                            {{ $msg->status_label }}
                                        </span>
                                    </td>
                                    <td>
                                        @if ($msg->delivery_status)
                                            <span class="account-badge {{ $msg->delivery_status === 'delivered' ? 'is-ok' : (in_array($msg->delivery_status, ['undelivered', 'failed']) ? 'is-danger' : 'is-warn') }}">
                                                {{ $msg->delivery_status_label }}
                                            </span>
                                        @else
                                            <span class="field-hint">—</span>
                                        @endif
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
