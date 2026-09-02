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

        @if (session('auth_status'))
            <p class="auth-note">{{ session('auth_status') }}</p>
        @endif

        @if ($errors->any())
            <div class="auth-errors">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('dashboard.profile.update', ['step' => $step]) }}"
            class="profile-form" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            @if ($step === 1)
                <div class="profile-grid">
                    <label>
                        <span>نام *</span>
                        <input type="text" name="first_name" value="{{ old('first_name', $user->first_name) }}" required maxlength="120" />
                    </label>
                    <label>
                        <span>نام خانوادگی *</span>
                        <input type="text" name="last_name" value="{{ old('last_name', $user->last_name) }}" required maxlength="120" />
                    </label>
                    <label>
                        <span>شرکت</span>
                        <input type="text" name="company" value="{{ old('company', $user->company) }}" maxlength="160" />
                    </label>
                    <label>
                        <span>ایمیل</span>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" dir="ltr" maxlength="180" />
                    </label>
                    <label>
                        <span>شماره تماس (ثابت)</span>
                        <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" dir="ltr" maxlength="30" placeholder="021xxxxxxxx" />
                    </label>
                    <label class="full">
                        <span>موبایل</span>
                        <input type="text" value="{{ $user->mobile }}" dir="ltr" disabled />
                    </label>
                    <label>
                        <span>رمز عبور (اختیاری)</span>
                        <input type="password" name="password" autocomplete="new-password" placeholder="برای ورود با رمز" />
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
                        <select name="country" required>
                            @foreach ($countries as $c)
                                <option value="{{ $c }}" @selected(old('country', $user->country ?? 'ایران') === $c)>{{ $c }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>
                        <span>استان</span>
                        <select name="province">
                            <option value="">—</option>
                            @foreach ($provinces as $p)
                                <option value="{{ $p }}" @selected(old('province', $user->province) === $p)>{{ $p }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>
                        <span>شهر</span>
                        <input type="text" name="city" value="{{ old('city', $user->city) }}" maxlength="120" />
                    </label>
                    <label>
                        <span>کد پستی</span>
                        <input type="text" name="postal_code" value="{{ old('postal_code', $user->postal_code) }}" dir="ltr" inputmode="numeric" maxlength="10" />
                    </label>
                    <label class="full">
                        <span>آدرس</span>
                        <textarea name="address" rows="2" maxlength="1000">{{ old('address', $user->address) }}</textarea>
                    </label>
                    <label class="full">
                        <span>توضیحات</span>
                        <textarea name="description" rows="2" maxlength="1000">{{ old('description', $user->description) }}</textarea>
                    </label>
                </div>
            @else
                <div class="profile-grid">
                    <label>
                        <span>کد ملی</span>
                        <input type="text" name="national_code" value="{{ old('national_code', $user->national_code) }}" dir="ltr" inputmode="numeric" maxlength="10" />
                    </label>
                    <label>
                        <span>شماره شناسنامه</span>
                        <input type="text" name="birth_cert_number" value="{{ old('birth_cert_number', $user->birth_cert_number) }}" dir="ltr" maxlength="20" />
                    </label>
                </div>

                @foreach ([
                    'national_card_image' => 'تصویر کارت ملی',
                    'national_card_back_image' => 'تصویر پشت کارت ملی',
                    'identity_doc_image' => 'تصویر احراز هویت',
                ] as $field => $label)
                    <label class="file-field">
                        <span>{{ $label }}</span>
                        <input type="file" name="{{ $field }}" accept="image/jpeg,image/png,image/webp" />
                        @if ($user->{$field})
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
