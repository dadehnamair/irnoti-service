@extends('layouts.account')

@section('title', 'ساخت کارت ویزیت دیجیتال')

@section('content')
    <div class="account-card">
        <h2>ساخت کارت ویزیت دیجیتال</h2>
        <p class="auth-sub">یکی از دامنه‌های زیر را انتخاب کنید و نوع کارت خود را مشخص کنید.</p>

        <form method="POST" action="{{ route('dashboard.cards.store') }}" class="profile-grid" data-card-form>
            @csrf

            <fieldset class="type-switch" style="grid-column:1 / -1">
                <legend>نوع کارت *</legend>
                <label class="type-switch__opt">
                    <input type="radio" name="tier" value="standard" checked data-tier-radio />
                    <span>VBC — کارت ویزیت مجازی — {{ number_format($standardPrice) }} تومان</span>
                </label>
                <label class="type-switch__opt">
                    <input type="radio" name="tier" value="vip" data-tier-radio />
                    <span>EBC — کارت ویزیت الکترونیکی (کد اختصاصی)</span>
                </label>
                @error('tier') <span class="field-error">{{ $message }}</span> @enderror
            </fieldset>

            <label class="full">
                <span>دامنه *</span>
                <select name="domain_id" required data-quote-field>
                    @foreach ($domains as $domain)
                        <option value="{{ $domain->id }}" @selected(old('domain_id') == $domain->id)>{{ $domain->label ?: $domain->host }} ({{ $domain->host }})</option>
                    @endforeach
                </select>
                @error('domain_id') <span class="field-error">{{ $message }}</span> @enderror
            </label>

            <label class="full">
                <span>کد اختصاصی (بخشی از لینک شما) *</span>
                <input type="text" name="code" dir="ltr" maxlength="32" required
                    value="{{ old('code') }}" placeholder="ali" data-quote-field />
                @error('code') <span class="field-error">{{ $message }}</span> @enderror
            </label>

            <div class="account-stat-grid" style="grid-column:1 / -1">
                <div class="account-stat">
                    <span>قیمت</span>
                    <strong data-quote-price>—</strong>
                </div>
            </div>

            <p class="field-error" data-quote-message hidden></p>

            <button type="submit" class="btn btn-primary full" style="grid-column:1 / -1" data-submit disabled>
                ساخت کارت
            </button>
        </form>

        <a class="checkout-back" href="{{ route('dashboard.cards') }}">بازگشت به کارت‌های من</a>
    </div>

    <script>
        (() => {
            const form = document.querySelector('[data-card-form]');
            if (!form) return;

            const tierRadios = Array.from(form.querySelectorAll('[data-tier-radio]'));
            const domainSelect = form.querySelector('select[name="domain_id"]');
            const codeInput = form.querySelector('input[name="code"]');
            const priceOut = form.querySelector('[data-quote-price]');
            const messageOut = form.querySelector('[data-quote-message]');
            const submitBtn = form.querySelector('[data-submit]');
            const standardPrice = {{ (int) $standardPrice }};
            const quoteUrl = @json(route('dashboard.cards.quote'));

            const currentTier = () => (tierRadios.find((r) => r.checked) || {}).value || 'standard';

            const setMessage = (text) => {
                if (!messageOut) return;
                messageOut.textContent = text || '';
                messageOut.hidden = !text;
            };

            const setPrice = (value) => {
                if (priceOut) priceOut.textContent = value === null ? '—' : `${new Intl.NumberFormat('fa-IR').format(value)} تومان`;
            };

            let requestId = 0;

            const quote = async () => {
                if (currentTier() !== 'vip') {
                    setMessage('');
                    setPrice(standardPrice);
                    if (submitBtn) submitBtn.disabled = false;
                    return;
                }

                const domainId = domainSelect ? domainSelect.value : '';
                const code = codeInput ? codeInput.value.trim() : '';

                if (!domainId || !code) {
                    setPrice(null);
                    setMessage('');
                    if (submitBtn) submitBtn.disabled = true;
                    return;
                }

                const myRequest = ++requestId;
                const params = new URLSearchParams({ domain_id: domainId, code });

                try {
                    const res = await fetch(`${quoteUrl}?${params.toString()}`, {
                        headers: { Accept: 'application/json' },
                    });
                    const data = await res.json();
                    if (myRequest !== requestId) return;

                    if (data.ok) {
                        setPrice(data.price);
                        setMessage('');
                        if (submitBtn) submitBtn.disabled = false;
                    } else {
                        setPrice(null);
                        setMessage(data.message || 'این کد قابل ثبت نیست.');
                        if (submitBtn) submitBtn.disabled = true;
                    }
                } catch (err) {
                    if (myRequest !== requestId) return;
                    setMessage('بررسی قیمت ممکن نشد.');
                    if (submitBtn) submitBtn.disabled = true;
                }
            };

            let debounce;
            const debouncedQuote = () => {
                window.clearTimeout(debounce);
                debounce = window.setTimeout(quote, 350);
            };

            tierRadios.forEach((radio) => radio.addEventListener('change', quote));
            if (domainSelect) domainSelect.addEventListener('change', debouncedQuote);
            if (codeInput) codeInput.addEventListener('input', debouncedQuote);

            quote();
        })();
    </script>
@endsection
