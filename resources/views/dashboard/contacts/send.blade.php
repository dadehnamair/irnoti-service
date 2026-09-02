@extends('layouts.account')

@section('title', 'ارسال گروهی')

@section('content')
    <div class="account-card">
        <div class="account-card__head">
            <h2>ارسال گروهی پیامک</h2>
            <a href="{{ route('dashboard.contacts') }}">بازگشت به دفترچه تلفن</a>
        </div>

        @unless ($hasPanel)
            <p class="auth-sub">پنل پیامک شما هنوز فعال نشده است.</p>
        @endunless

        <form method="POST" action="{{ route('dashboard.contacts.send.post') }}" class="account-form">
            @csrf

            @include('dashboard.contacts.partials.group-picker', [
                'selectedGroupIds' => collect(old('groups', [])),
                'withCounts' => true,
                'markUnsynced' => true,
            ])

            <label>
                <span>شماره‌های اضافی (فقط حالت محلی — با فاصله یا کاما جدا کنید)</span>
                <textarea name="numbers" rows="3" dir="ltr" placeholder="09121234567, 09351234567">{{ old('numbers') }}</textarea>
            </label>

            <label>
                <span>سرشماره فرستنده</span>
                <select name="from">
                    @foreach ($numbers as $number)
                        <option value="{{ $number }}" @selected(old('from', $defaultSender) === $number)>{{ $number }}</option>
                    @endforeach
                </select>
            </label>

            <label>
                <span>متن پیام</span>
                <textarea name="message" rows="4" maxlength="600" required>{{ old('message') }}</textarea>
            </label>

            <label>
                <span>روش ارسال</span>
                <select name="mode">
                    <option value="local" @selected(old('mode') === 'local')>محلی — ارسال تکی به شماره‌های ذخیره‌شده (حداکثر {{ $localCap }} گیرنده)</option>
                    <option value="melipayamak" @selected(old('mode') === 'melipayamak')>ملی‌پیامک — ارسال به گروه‌های همگام‌شده (حداکثر ۵ گروه)</option>
                </select>
            </label>

            <label>
                <span>زمان‌بندی ارسال (اختیاری — فقط حالت ملی‌پیامک)</span>
                <input type="datetime-local" name="schedule_at" dir="ltr" value="{{ old('schedule_at') }}" />
            </label>

            <button type="submit" class="btn btn-primary full">ارسال</button>
        </form>
    </div>
@endsection
