@php
    /** @var \App\Models\Contact|null $contact */
    $method = $method ?? 'POST';
    $selectedGroups = collect(old('groups', $contact?->groups->pluck('id')->all() ?? []))->map(fn ($v) => (int) $v);
@endphp

<form method="POST" action="{{ $action }}" class="account-form contact-form">
    @csrf
    @if ($method !== 'POST') @method($method) @endif

    <div class="contact-form__grid">
        <label>
            <span>نام</span>
            <input type="text" name="first_name" value="{{ old('first_name', $contact?->first_name) }}" />
        </label>
        <label>
            <span>نام خانوادگی</span>
            <input type="text" name="last_name" value="{{ old('last_name', $contact?->last_name) }}" />
        </label>
        <label>
            <span>موبایل *</span>
            <input type="text" name="mobile" dir="ltr" required
                   value="{{ old('mobile', $contact?->mobile) }}" placeholder="09121234567" />
        </label>
        <label>
            <span>نام مستعار</span>
            <input type="text" name="nickname" value="{{ old('nickname', $contact?->nickname) }}" />
        </label>
        <label>
            <span>شرکت</span>
            <input type="text" name="company" value="{{ old('company', $contact?->company) }}" />
        </label>
        <label>
            <span>ایمیل</span>
            <input type="email" name="email" dir="ltr" value="{{ old('email', $contact?->email) }}" />
        </label>
        <label>
            <span>جنسیت</span>
            <select name="gender">
                <option value="">—</option>
                @foreach (\App\Models\Contact::GENDERS as $key => $label)
                    <option value="{{ $key }}" @selected(old('gender', $contact?->gender) === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label>
            @php($birthGregorian = old('birth_date', $contact?->birth_date?->toDateString()))
            <span>تاریخ تولد</span>
            <input type="text" data-jdp data-jdp-only-date
                   data-jdp-target-value-input="#birth_date_value" data-jdp-target-value-type="gregorian"
                   dir="ltr" autocomplete="off" inputmode="numeric" placeholder="۱۳۷۰/۰۵/۱۷"
                   value="{{ $birthGregorian ? fa_digits(jalali_date($birthGregorian)) : '' }}" />
            <input type="hidden" id="birth_date_value" name="birth_date" value="{{ $birthGregorian }}" />
        </label>
    </div>

    <label>
        <span>توضیحات</span>
        <textarea name="description" rows="2">{{ old('description', $contact?->description) }}</textarea>
    </label>

    @if ($groups->isNotEmpty())
        @include('dashboard.contacts.partials.group-picker', ['selectedGroupIds' => $selectedGroups])
    @else
        <p class="auth-sub">
            هنوز گروهی نساخته‌اید. برای همگام‌سازی مخاطب با {{ sms_provider_label() }}، ابتدا یک
            <a href="{{ route('dashboard.contacts.groups') }}">گروه</a> بسازید و همگام کنید.
        </p>
    @endif

    <button type="submit" class="btn btn-primary">{{ $contact ? 'ذخیرهٔ تغییرات' : 'افزودن مخاطب' }}</button>
</form>
