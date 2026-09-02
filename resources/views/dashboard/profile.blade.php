@extends('layouts.account')

@section('title', 'تکمیل اطلاعات')

@section('content')
    <div class="account-card">
        <h2>تکمیل اطلاعات حساب</h2>

        <ol class="wizard-steps">
            @foreach ($stepLabels as $n => $label)
                <li @class([
                    'done' => $n < $step,
                    'current' => $n === $step,
                ])>{{ $n }}. {{ $label }}</li>
            @endforeach
        </ol>

        @if ($step === 1 && $user->identityLocked())
            <p class="account-inline-note is-info">
                نام و نام خانوادگی پس از تأیید حساب قابل ویرایش نیست؛ برای اصلاح با پشتیبانی تماس بگیرید.
            </p>
        @endif

        <form method="POST" action="{{ route('dashboard.profile.update', ['step' => $step]) }}"
            class="profile-form" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            @if ($step === 1)
                @php($lock = $user->identityLocked())
                <div class="profile-grid">
                    <label>
                        <span>نام *</span>
                        <input type="text" name="first_name" value="{{ old('first_name', $user->first_name) }}"
                            @class(['has-error' => $errors->has('first_name')]) required maxlength="120" @readonly($lock) />
                        @error('first_name') <span class="field-error">{{ $message }}</span> @enderror
                    </label>
                    <label>
                        <span>نام خانوادگی *</span>
                        <input type="text" name="last_name" value="{{ old('last_name', $user->last_name) }}"
                            @class(['has-error' => $errors->has('last_name')]) required maxlength="120" @readonly($lock) />
                        @error('last_name') <span class="field-error">{{ $message }}</span> @enderror
                    </label>
                    <label>
                        <span>شرکت</span>
                        <input type="text" name="company" value="{{ old('company', $user->company) }}" maxlength="160" />
                        @error('company') <span class="field-error">{{ $message }}</span> @enderror
                    </label>
                    <label>
                        <span>ایمیل</span>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" dir="ltr" maxlength="180"
                            @class(['has-error' => $errors->has('email')]) />
                        @error('email') <span class="field-error">{{ $message }}</span> @enderror
                    </label>
                    <label>
                        <span>شماره تماس (ثابت)</span>
                        <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" dir="ltr" maxlength="30" placeholder="021xxxxxxxx" />
                        @error('phone') <span class="field-error">{{ $message }}</span> @enderror
                    </label>
                    <label class="full">
                        <span>موبایل</span>
                        <input type="text" value="{{ $user->mobile }}" dir="ltr" disabled />
                    </label>
                    <label>
                        <span>رمز عبور (اختیاری)</span>
                        <input type="password" name="password" autocomplete="new-password" placeholder="برای ورود با رمز"
                            @class(['has-error' => $errors->has('password')]) />
                        @error('password') <span class="field-error">{{ $message }}</span> @enderror
                    </label>
                    <label>
                        <span>تکرار رمز عبور</span>
                        <input type="password" name="password_confirmation" autocomplete="new-password" />
                    </label>
                </div>
            @elseif ($step === 2)
                <div class="profile-grid">
                    <label>
                        <span>کشور *</span>
                        <select name="country" required @class(['has-error' => $errors->has('country')])>
                            @foreach ($countries as $c)
                                <option value="{{ $c }}" @selected(old('country', $user->country ?? 'ایران') === $c)>{{ $c }}</option>
                            @endforeach
                        </select>
                        @error('country') <span class="field-error">{{ $message }}</span> @enderror
                    </label>
                    <label>
                        <span>استان</span>
                        <select name="province" @class(['has-error' => $errors->has('province')])>
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
                            inputmode="numeric" maxlength="10" @class(['has-error' => $errors->has('postal_code')]) />
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

                <div class="profile-grid">
                    <label>
                        <span>کد ملی</span>
                        <input type="text" name="national_code" value="{{ old('national_code', $user->national_code) }}" dir="ltr"
                            inputmode="numeric" maxlength="10" @class(['has-error' => $errors->has('national_code')]) @readonly($lock) />
                        @error('national_code') <span class="field-error">{{ $message }}</span> @enderror
                    </label>
                    <label>
                        <span>شماره شناسنامه</span>
                        <input type="text" name="birth_cert_number" value="{{ old('birth_cert_number', $user->birth_cert_number) }}"
                            dir="ltr" maxlength="20" @class(['has-error' => $errors->has('birth_cert_number')]) @readonly($lock) />
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
@endsection
