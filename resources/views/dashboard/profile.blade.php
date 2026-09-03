@extends('layouts.account')

@section('title', 'تکمیل اطلاعات')

@section('content')
@php($accountType = old('account_type', $user->account_type ?? 'individual'))
<div class="account-card">
    <h2>تکمیل اطلاعات حساب</h2>

    <ol class="wizard-steps">
        @foreach ($stepLabels as $n => $label)
        <li @class([ 'done'=> $n < $step, 'current'=> $n === $step,
                ])>{{ $n }}. {{ $label }}</li>
        @endforeach
    </ol>

    @if ($step === 1 && $user->identityLocked())
    <p class="account-inline-note is-info">
        اطلاعات پایه پس از تأیید حساب قابل ویرایش نیست؛ برای اصلاح با پشتیبانی تماس بگیرید.
    </p>
    @endif

    <form method="POST" action="{{ route('dashboard.profile.update', ['step' => $step]) }}"
        class="profile-form" enctype="multipart/form-data" data-account-type="{{ $accountType }}">
        @csrf
        @method('PUT')

        @if ($step === 1)
        @php($lock = $user->identityLocked())

        <fieldset class="type-switch" @disabled($lock)>
            <legend>نوع حساب *</legend>
            @foreach ($accountTypes as $value => $label)
            <label class="type-switch__opt">
                <input type="radio" name="account_type" value="{{ $value }}"
                    @checked($accountType === $value) @disabled($lock)
                    data-account-type-radio />
                <span>{{ $label }}</span>
            </label>
            @endforeach
            @error('account_type') <span class="field-error">{{ $message }}</span> @enderror
        </fieldset>
        @if ($lock)
        <input type="hidden" name="account_type" value="{{ $user->account_type ?? 'individual' }}" />
        @endif

        <p class="account-inline-note is-info" data-when="legal">
            نام و نام خانوادگی زیر، مربوط به <strong>نمایندهٔ امضاکننده</strong> شرکت است. مدارک هویتی همین شخص در مرحلهٔ «احراز هویت» بارگذاری می‌شود.
        </p>

        <div class="profile-grid">
            <label>
                <span data-label-for="legal" data-label-text="نام نماینده *">نام *</span>
                <input type="text" name="first_name" value="{{ old('first_name', $user->first_name) }}"
                    @class(['has-error'=> $errors->has('first_name')]) required maxlength="120" @readonly($lock) />
                @error('first_name') <span class="field-error">{{ $message }}</span> @enderror
            </label>
            <label>
                <span data-label-for="legal" data-label-text="نام خانوادگی نماینده *">نام خانوادگی *</span>
                <input type="text" name="last_name" value="{{ old('last_name', $user->last_name) }}"
                    @class(['has-error'=> $errors->has('last_name')]) required maxlength="120" @readonly($lock) />
                @error('last_name') <span class="field-error">{{ $message }}</span> @enderror
            </label>
            <label data-when="legal">
                <span>سمت نماینده</span>
                <input type="text" name="rep_role" value="{{ old('rep_role', $user->rep_role) }}"
                    maxlength="120" placeholder="مدیرعامل، رئیس هیئت‌مدیره، …" />
                @error('rep_role') <span class="field-error">{{ $message }}</span> @enderror
            </label>
            <label class="full">
                <span data-label-for="legal" data-label-text="نام کامل شرکت *">شرکت</span>
                <input type="text" name="company" value="{{ old('company', $user->company) }}" maxlength="200"
                    @class(['has-error'=> $errors->has('company')]) @readonly($lock) />
                @error('company') <span class="field-error">{{ $message }}</span> @enderror
            </label>
            <label>
                <span>ایمیل</span>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" dir="ltr" maxlength="180"
                    @class(['has-error'=> $errors->has('email')]) @readonly($lock) />
                @error('email') <span class="field-error">{{ $message }}</span> @enderror
            </label>
            <label>
                <span data-label-for="legal" data-label-text="شماره تماس نماینده">شماره تماس (ثابت)</span>
                <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" dir="ltr" maxlength="30" placeholder="021xxxxxxxx" @readonly($lock) />
                @error('phone') <span class="field-error">{{ $message }}</span> @enderror
            </label>
            <label class="full">
                <span>موبایل</span>
                <input type="text" value="{{ $user->mobile }}" dir="ltr" disabled />
            </label>
            <label>
                <span>رمز عبور (اختیاری)</span>
                <input type="password" name="password" autocomplete="new-password" placeholder="برای ورود با رمز"
                    @class(['has-error'=> $errors->has('password')]) />
                @error('password') <span class="field-error">{{ $message }}</span> @enderror
            </label>
            <label>
                <span>تکرار رمز عبور</span>
                <input type="password" name="password_confirmation" autocomplete="new-password" />
            </label>
        </div>

        <div class="profile-subhead" data-when="legal">
            <h3>مشخصات و اطلاعات ثبتی شرکت</h3>
        </div>
        <div class="profile-grid" data-when="legal">
            <label>
                <span>نوع شخصیت حقوقی *</span>
                <select name="company_type" @class(['has-error'=> $errors->has('company_type')]) @disabled($lock)>
                    @php($companyTypes = ['سهامی خاص', 'سهامی عام', 'مسئولیت محدود', 'تعاونی', 'تضامنی', 'مؤسسه غیرتجاری', 'نسبی', 'دولتی / عمومی', 'سایر'])
                    <option value="">—</option>
                    @foreach ($companyTypes as $ct)
                    <option value="{{ $ct }}" @selected(old('company_type', $user->company_type) === $ct)>{{ $ct }}</option>
                    @endforeach
                </select>
                @error('company_type') <span class="field-error">{{ $message }}</span> @enderror
            </label>
            <label>
                <span>شناسه ملی شرکت *</span>
                <input type="text" name="company_national_id" value="{{ old('company_national_id', $user->company_national_id) }}"
                    dir="ltr" inputmode="numeric" maxlength="11"
                    @class(['has-error'=> $errors->has('company_national_id')]) @readonly($lock) />
                @error('company_national_id') <span class="field-error">{{ $message }}</span> @enderror
            </label>
            <label>
                <span>شماره ثبت *</span>
                <input type="text" name="company_registration_number" value="{{ old('company_registration_number', $user->company_registration_number) }}"
                    dir="ltr" maxlength="40"
                    @class(['has-error'=> $errors->has('company_registration_number')]) @readonly($lock) />
                @error('company_registration_number') <span class="field-error">{{ $message }}</span> @enderror
            </label>
            <label>
                <span>تاریخ ثبت</span>
                <input type="text" name="company_registered_at" value="{{ fa_digits(old('company_registered_at', $user->company_registered_at)) }}"
                    @unless($lock) data-jdp data-jdp-only-date @endunless
                    dir="ltr" maxlength="20" autocomplete="off" inputmode="numeric" placeholder="۱۴۰۲/۰۵/۱۷"
                    @class(['has-error'=> $errors->has('company_registered_at')]) @readonly($lock) />
                @error('company_registered_at') <span class="field-error">{{ $message }}</span> @enderror
            </label>
            <label>
                <span>کد اقتصادی</span>
                <input type="text" name="company_economic_code" value="{{ old('company_economic_code', $user->company_economic_code) }}"
                    dir="ltr" inputmode="numeric" maxlength="30"
                    @class(['has-error'=> $errors->has('company_economic_code')]) @readonly($lock) />
                @error('company_economic_code') <span class="field-error">{{ $message }}</span> @enderror
            </label>
            <label>
                <span>تلفن شرکت</span>
                <input type="text" name="company_phone" value="{{ old('company_phone', $user->company_phone) }}"
                    dir="ltr" maxlength="30" placeholder="021xxxxxxxx" />
                @error('company_phone') <span class="field-error">{{ $message }}</span> @enderror
            </label>
            <label>
                <span>کد پستی شرکت</span>
                <input type="text" name="company_postal_code" value="{{ old('company_postal_code', $user->company_postal_code) }}"
                    dir="ltr" inputmode="numeric" maxlength="10"
                    @class(['has-error'=> $errors->has('company_postal_code')]) />
                @error('company_postal_code') <span class="field-error">{{ $message }}</span> @enderror
            </label>
            <label class="full">
                <span>نشانی شرکت</span>
                <textarea name="company_address" rows="2" maxlength="1000">{{ old('company_address', $user->company_address) }}</textarea>
                @error('company_address') <span class="field-error">{{ $message }}</span> @enderror
            </label>
        </div>
        @elseif ($step === 2)
        <div class="profile-grid">
            <label>
                <span>کشور *</span>
                <select name="country" required @class(['has-error'=> $errors->has('country')])>
                    @foreach ($countries as $c)
                    <option value="{{ $c }}" @selected(old('country', $user->country ?? 'ایران') === $c)>{{ $c }}</option>
                    @endforeach
                </select>
                @error('country') <span class="field-error">{{ $message }}</span> @enderror
            </label>
            <label>
                <span>استان</span>
                <select name="province" @class(['has-error'=> $errors->has('province')])>
                    <option value="">—</option>
                    @foreach ($provinces as $p)
                    <option value="{{ $p }}" @selected(old('province', $user->province) === $p)>{{ $p }}</option>
                    @endforeach
                </select>
                @error('province') <span class="field-error">{{ $message }}</span> @enderror
            </label>
            <label>
                <span>شهر</span>
                <input type="text" name="city" value="{{ old('city', $user->city) }}" maxlength="120" />
                @error('city') <span class="field-error">{{ $message }}</span> @enderror
            </label>
            <label>
                <span>کد پستی</span>
                <input type="text" name="postal_code" value="{{ old('postal_code', $user->postal_code) }}" dir="ltr"
                    inputmode="numeric" maxlength="10" @class(['has-error'=> $errors->has('postal_code')]) />
                @error('postal_code') <span class="field-error">{{ $message }}</span> @enderror
            </label>
            <label class="full">
                <span>آدرس</span>
                <textarea name="address" rows="2" maxlength="1000">{{ old('address', $user->address) }}</textarea>
                @error('address') <span class="field-error">{{ $message }}</span> @enderror
            </label>
            <label class="full">
                <span>توضیحات</span>
                <textarea name="description" rows="2" maxlength="1000">{{ old('description', $user->description) }}</textarea>
                @error('description') <span class="field-error">{{ $message }}</span> @enderror
            </label>
        </div>
        @else
        @php($lock = $user->identityLocked())
        @php($docsLock = $user->documentsLocked())

        @if ($user->documents_status === 'rejected' && $user->documents_reject_reason)
        <p class="account-inline-note is-danger">مدارک شما رد شد. دلیل: {{ $user->documents_reject_reason }}. لطفاً دوباره بارگذاری کنید.</p>
        @elseif ($docsLock)
        <p class="account-inline-note is-ok">مدارک شما تأیید شده است و قابل تغییر نیست.</p>
        @elseif ($user->national_card_image)
        <p class="account-inline-note is-info">مدارک شما در انتظار بررسی توسط کارشناسان است.</p>
        @endif

        @if ($user->isLegal())
        <div class="profile-subhead"><h3>مدارک هویتی نمایندهٔ امضاکننده</h3></div>
        @endif

        <div class="profile-grid">
            <label>
                <span>کد ملی{{ $user->isLegal() ? ' نماینده' : '' }}</span>
                <input type="text" name="national_code" value="{{ old('national_code', $user->national_code) }}" dir="ltr"
                    inputmode="numeric" maxlength="10" @class(['has-error'=> $errors->has('national_code')]) @readonly($lock) />
                @error('national_code') <span class="field-error">{{ $message }}</span> @enderror
            </label>
            <label>
                <span>شماره شناسنامه</span>
                <input type="text" name="birth_cert_number" value="{{ old('birth_cert_number', $user->birth_cert_number) }}"
                    dir="ltr" maxlength="20" @class(['has-error'=> $errors->has('birth_cert_number')]) @readonly($lock) />
                @error('birth_cert_number') <span class="field-error">{{ $message }}</span> @enderror
            </label>
        </div>

        @foreach ([
        'national_card_image' => 'تصویر کارت ملی',
        'national_card_back_image' => 'تصویر پشت کارت ملی',
        'identity_doc_image' => 'تصویر احراز هویت',
        ] as $field => $label)
        <label class="file-field">
            <span>{{ $label }}</span>
            <input type="file" name="{{ $field }}" accept="image/jpeg,image/png,image/webp" @disabled($docsLock) />
            @error($field) <span class="field-error">{{ $message }}</span> @enderror
            @if ($user->{$field} && ! $docsLock)
            <span class="current">✓ فایل بارگذاری‌شده — برای جایگزینی فایل جدید انتخاب کنید</span>
            @endif
        </label>
        @endforeach

        @if ($user->isLegal())
        <div class="profile-subhead"><h3>مدارک شرکت</h3></div>

        @foreach ([
        'company_registration_doc' => 'آگهی تأسیس / روزنامه رسمی *',
        'company_changes_doc' => 'آگهی آخرین تغییرات',
        ] as $field => $label)
        <label class="file-field">
            <span>{{ $label }}</span>
            <input type="file" name="{{ $field }}" accept="image/jpeg,image/png,image/webp,application/pdf" @disabled($docsLock) />
            @error($field) <span class="field-error">{{ $message }}</span> @enderror
            @if ($user->{$field} && ! $docsLock)
            <span class="current">✓ فایل بارگذاری‌شده — برای جایگزینی فایل جدید انتخاب کنید</span>
            @endif
        </label>
        @endforeach

        <label class="file-field">
            <span>مدارک اضافه (اختیاری — پروانه، مجوز، …)</span>
            <input type="file" name="company_extra_docs[]" multiple
                accept="image/jpeg,image/png,image/webp,application/pdf" @disabled($docsLock) />
            @error('company_extra_docs') <span class="field-error">{{ $message }}</span> @enderror
            @error('company_extra_docs.*') <span class="field-error">{{ $message }}</span> @enderror
            @if (!empty($user->company_extra_docs) && ! $docsLock)
            <span class="current">✓ {{ count($user->company_extra_docs) }} فایل بارگذاری‌شده — انتخاب فایل جدید به فهرست اضافه می‌کند</span>
            @endif
        </label>
        @endif
        @endif

        <div class="profile-actions">
            @if ($step > 1)
            <a class="btn btn-ghost" href="{{ route('dashboard.profile.step', ['step' => $step - 1]) }}">مرحله قبل</a>
            @else
            <span></span>
            @endif

            <button type="submit" class="btn btn-primary">
                {{ $step === $lastStep ? 'ذخیره و پایان' : 'ذخیره و ادامه' }}
            </button>
        </div>
    </form>
</div>

@if ($step === 1)
<style>
    .type-switch { border: 1px solid color-mix(in srgb, var(--primary) 22%, transparent); border-radius: 12px; padding: .75rem 1rem 1rem; margin-bottom: 1.25rem; display: flex; flex-wrap: wrap; gap: 1.25rem; align-items: center; }
    .type-switch legend { font-weight: 700; padding-inline: .35rem; }
    .type-switch__opt { display: inline-flex; align-items: center; gap: .4rem; cursor: pointer; font-weight: 600; }
    .profile-subhead { margin: 1.5rem 0 .5rem; }
    .profile-subhead h3 { font-size: 1rem; font-weight: 700; margin: 0; padding-bottom: .35rem; border-bottom: 1px solid color-mix(in srgb, var(--primary) 18%, transparent); }
    .profile-form[data-account-type="individual"] [data-when="legal"] { display: none; }
    .profile-form[data-account-type="legal"] [data-when="individual"] { display: none; }
</style>
<script>
    (function () {
        var form = document.querySelector('.profile-form[data-account-type]');
        if (!form) return;

        function apply(type) {
            form.setAttribute('data-account-type', type);
            form.querySelectorAll('[data-when]').forEach(function (el) {
                var on = el.getAttribute('data-when') === type;
                el.querySelectorAll('input, select, textarea').forEach(function (ctrl) {
                    ctrl.disabled = !on;
                });
            });
            form.querySelectorAll('[data-label-for]').forEach(function (span) {
                var alt = span.getAttribute('data-label-for') === type ? span.getAttribute('data-label-text') : null;
                if (span.dataset.original === undefined) span.dataset.original = span.textContent;
                span.textContent = alt || span.dataset.original;
            });
        }

        form.querySelectorAll('[data-account-type-radio]').forEach(function (radio) {
            radio.addEventListener('change', function () { if (radio.checked) apply(radio.value); });
        });

        var checked = form.querySelector('[data-account-type-radio]:checked');
        apply(checked ? checked.value : 'individual');
    })();
</script>
@endif
@endsection
