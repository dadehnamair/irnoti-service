@extends('layouts.account')

@section('title', 'ویرایش کارت ویزیت دیجیتال')

@section('content')
    <div class="account-card">
        <div class="account-card__head">
            <h2>{{ $card->title ?: $card->code }}</h2>
            <span class="account-badge {{ $card->status === 'active' ? 'is-ok' : ($card->status === 'awaiting_payment' ? 'is-warn' : 'is-info') }}" data-card-status-badge>
                {{ $card->status_label }}
            </span>
        </div>

        @if ($card->status === 'active')
            <p class="auth-sub">
                لینک عمومی: <a dir="ltr" href="{{ $card->public_url }}" target="_blank" rel="noopener">{{ $card->public_url }}</a>
            </p>
        @endif

        @if ($card->isPayable())
            <div class="account-card" style="margin-top:16px" data-payment-box>
                <h3>تکمیل پرداخت</h3>
                <div class="account-stat-grid">
                    <div class="account-stat">
                        <span>مبلغ</span>
                        <strong>{{ number_format($card->price) }} تومان</strong>
                    </div>
                    <div class="account-stat">
                        <span>موجودی کیف پول</span>
                        <strong data-wallet-balance>{{ number_format($walletBalance) }} تومان</strong>
                    </div>
                </div>

                <div class="account-actions" style="margin-top:12px">
                    <form method="POST" action="{{ route('dashboard.cards.wallet', $card) }}" data-ajax data-busy-label="در حال پرداخت…">
                        @csrf
                        <button type="submit" class="btn btn-primary" @disabled($walletBalance < $card->price)>
                            پرداخت از کیف پول
                        </button>
                    </form>

                    @if ($onlinePayment)
                        <a class="btn btn-secondary" href="{{ route('dashboard.cards.pay', $card) }}">پرداخت آنلاین</a>
                    @endif
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('dashboard.cards.update', $card) }}" class="profile-grid"
            enctype="multipart/form-data" data-ajax data-busy-label="در حال ذخیره…" style="margin-top:16px">
            @csrf
            @method('PUT')

            <label class="full">
                <span>تصویر پروفایل</span>
                <img data-preview="avatar" src="{{ $card->avatar_path ? \Illuminate\Support\Facades\Storage::url($card->avatar_path) : '' }}"
                    style="width:72px;height:72px;border-radius:50%;object-fit:cover;background:#f3f4f6;display:{{ $card->avatar_path ? 'block' : 'none' }};margin-bottom:8px" />
                <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp" data-input-preview="avatar" />
                @error('avatar') <span class="field-error">{{ $message }}</span> @enderror
            </label>

            <label class="full">
                <span>تصویر کاور</span>
                <img data-preview="cover" src="{{ $card->cover_path ? \Illuminate\Support\Facades\Storage::url($card->cover_path) : '' }}"
                    style="width:100%;max-width:320px;height:90px;border-radius:12px;object-fit:cover;background:#f3f4f6;display:{{ $card->cover_path ? 'block' : 'none' }};margin-bottom:8px" />
                <input type="file" name="cover" accept="image/jpeg,image/png,image/webp" data-input-preview="cover" />
                @error('cover') <span class="field-error">{{ $message }}</span> @enderror
            </label>

            <label>
                <span>عنوان (نام و نام‌خانوادگی)</span>
                <input type="text" name="title" value="{{ $card->title }}" maxlength="150" />
                @error('title') <span class="field-error">{{ $message }}</span> @enderror
            </label>
            <label>
                <span>سمت</span>
                <input type="text" name="position" value="{{ $card->position }}" maxlength="150" />
                @error('position') <span class="field-error">{{ $message }}</span> @enderror
            </label>
            <label class="full">
                <span>شرکت</span>
                <input type="text" name="company" value="{{ $card->company }}" maxlength="150" />
                @error('company') <span class="field-error">{{ $message }}</span> @enderror
            </label>
            <label class="full">
                <span>بیوگرافی</span>
                <textarea name="bio" rows="3" maxlength="2000">{{ $card->bio }}</textarea>
                @error('bio') <span class="field-error">{{ $message }}</span> @enderror
            </label>
            <label>
                <span>تلفن ثابت</span>
                <input type="text" name="phone" dir="ltr" value="{{ $card->phone }}" maxlength="20" />
                @error('phone') <span class="field-error">{{ $message }}</span> @enderror
            </label>
            <label>
                <span>موبایل</span>
                <input type="text" name="mobile" dir="ltr" value="{{ $card->mobile }}" maxlength="20" />
                @error('mobile') <span class="field-error">{{ $message }}</span> @enderror
            </label>
            <label>
                <span>واتساپ</span>
                <input type="text" name="whatsapp" dir="ltr" value="{{ $card->whatsapp }}" maxlength="20" />
                @error('whatsapp') <span class="field-error">{{ $message }}</span> @enderror
            </label>
            <label>
                <span>تلگرام</span>
                <input type="text" name="telegram" dir="ltr" value="{{ $card->telegram }}" maxlength="100" />
                @error('telegram') <span class="field-error">{{ $message }}</span> @enderror
            </label>
            <label>
                <span>اینستاگرام</span>
                <input type="text" name="instagram" dir="ltr" value="{{ $card->instagram }}" maxlength="100" />
                @error('instagram') <span class="field-error">{{ $message }}</span> @enderror
            </label>
            <label>
                <span>وبسایت</span>
                <input type="url" name="website" dir="ltr" value="{{ $card->website }}" maxlength="190" />
                @error('website') <span class="field-error">{{ $message }}</span> @enderror
            </label>
            <label>
                <span>ایمیل</span>
                <input type="email" name="email" dir="ltr" value="{{ $card->email }}" maxlength="190" />
                @error('email') <span class="field-error">{{ $message }}</span> @enderror
            </label>
            <label>
                <span>رنگ اصلی</span>
                <input type="color" name="theme_color" value="{{ $card->theme_color ?: '#ff3000' }}" />
                @error('theme_color') <span class="field-error">{{ $message }}</span> @enderror
            </label>
            <label class="full">
                <span>آدرس</span>
                <input type="text" name="address" value="{{ $card->address }}" maxlength="255" />
                @error('address') <span class="field-error">{{ $message }}</span> @enderror
            </label>

            <button type="submit" class="btn btn-primary full" style="grid-column:1 / -1">ذخیره اطلاعات</button>
        </form>

        <a class="checkout-back" href="{{ route('dashboard.cards') }}">بازگشت به کارت‌های من</a>
    </div>

    <script>
        (() => {
            // Instant local preview when picking a new avatar/cover, before upload finishes.
            document.querySelectorAll('[data-input-preview]').forEach((input) => {
                input.addEventListener('change', () => {
                    const file = input.files && input.files[0];
                    if (!file) return;
                    const img = document.querySelector(`[data-preview="${input.dataset.inputPreview}"]`);
                    if (!img) return;
                    img.src = URL.createObjectURL(file);
                    img.style.display = 'block';
                });
            });

            // After a successful wallet payment, drop the payment box and flip the status badge
            // in place instead of reloading the page.
            const walletForm = document.querySelector('[data-payment-box] form[data-ajax]');
            if (walletForm) {
                walletForm.addEventListener('ajax:success', (event) => {
                    const data = event.detail || {};
                    const badge = document.querySelector('[data-card-status-badge]');
                    if (badge && data.status_label) {
                        badge.textContent = data.status_label;
                        badge.classList.remove('is-warn', 'is-info');
                        badge.classList.add('is-ok');
                    }
                    document.querySelector('[data-payment-box]')?.remove();
                });
            }
        })();
    </script>
@endsection
