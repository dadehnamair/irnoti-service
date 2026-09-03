@extends('layouts.account')

@section('title', 'ارسال به ' . $channelLabel)

@section('content')
    <div class="account-card">
        <div class="account-card__head">
            <h2>ارسال به {{ $channelLabel }}</h2>
            <a href="{{ route('dashboard.messenger') }}">بازگشت</a>
        </div>

        <div class="account-stat-grid">
            <div class="account-stat">
                <span>موجودی کیف پول</span>
                <strong>@toman($walletBalance) تومان</strong>
            </div>
            <div class="account-stat">
                <span>تعرفهٔ هر گیرنده</span>
                <strong>
                    @if ($tariff > 0)
                        @toman($tariff) تومان
                    @else
                        رایگان
                    @endif
                </strong>
            </div>
        </div>

        <form method="POST" action="{{ route('dashboard.messenger.send') }}" class="account-form">
            @csrf
            <input type="hidden" name="channel" value="{{ $channel }}" />

            @include('dashboard.contacts.partials.group-picker', [
                'selectedGroupIds' => collect(old('groups', [])),
                'withCounts' => true,
                'markUnsynced' => false,
            ])

            <label>
                <span>شماره‌ها یا شناسه‌ها (با فاصله، کاما یا خط جدید جدا کنید)</span>
                <textarea name="recipients" rows="4" dir="ltr"
                          placeholder="09121234567&#10;09351234567&#10;@username">{{ old('recipients') }}</textarea>
            </label>

            <label>
                <span>متن پیام *</span>
                <textarea name="message" rows="5" maxlength="1000" required>{{ old('message') }}</textarea>
                @error('message') <span class="field-error">{{ $message }}</span> @enderror
            </label>

            <label>
                <span>زمان‌بندی ارسال (اختیاری)</span>
                <input type="datetime-local" name="schedule_at" dir="ltr" value="{{ old('schedule_at') }}" />
            </label>

            <p class="field-hint">حداکثر {{ number_format($cap) }} گیرنده در هر ارسال.</p>

            <button type="submit" class="btn btn-primary full">ارسال</button>
        </form>
    </div>
@endsection
