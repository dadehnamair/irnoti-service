@php
/**
* Account sidebar credit card (docs/starter.md §15 / §23). A graphic
* identity + balance tile that sits above the section nav. The panel
* credit is read live from Melipayamak (60s cache) and always shown in
* Toman — never Rial (§12). rescue()d so a fresh/empty DB still renders.
*/
$ccUser = auth()->user();
$ccWallet = (int) rescue(fn () => $ccUser->walletBalance(), 0, false);
$ccPanel = rescue(fn () => $ccUser->smsPanelCredit(), ['sms' => null, 'rial' => null, 'error' => null], false);
$ccHasPanel = $ccPanel['rial'] !== null || $ccPanel['sms'] !== null;
$ccAmount = $ccPanel['rial'] !== null ? rial_to_toman($ccPanel['rial']) : $ccWallet;
$ccInitial = mb_substr(trim((string) $ccUser?->full_name), 0, 1) ?: 'ک';

// انقضای پلن پنل — تاریخ جلالی + شمارش روزهای باقی‌مانده (docs/starter.md §8/§15)
$ccPlanExpires = $ccUser?->plan_expires_at;
$ccPlanDays = $ccPlanExpires
? (int) ceil(now()->startOfDay()->diffInDays($ccPlanExpires->copy()->startOfDay(), false))
: null;
@endphp

@if ($ccUser)
<div class="credit-card" role="group" aria-label="اعتبار حساب">
    <span class="credit-card__glow" aria-hidden="true"></span>

    <div class="credit-card__head">
        <span class="credit-card__avatar" aria-hidden="true">{{ $ccInitial }}</span>
        <span class="credit-card__id">
            <strong>{{ $ccUser->full_name }}</strong>
            <span dir="ltr">{{ $ccUser->mobile }}</span>
        </span>
    </div>

    <div class="credit-card__body">
        <span class="credit-card__label">{{ $ccHasPanel ? 'اعتبار پنل پیامک' : 'موجودی کیف پول' }}</span>
        <span class="credit-card__amount">@toman($ccAmount)<span class="credit-card__unit">تومان</span></span>
        <span class="credit-card__meta">
            @if ($ccPanel['sms'] !== null)
            {{ number_format($ccPanel['sms']) }} پیامک باقی‌مانده
            @elseif ($ccPanel['error'])
            اتصال به پنل پیامک برقرار نشد
            @else
            قابل استفاده برای خرید سرویس‌ها
            @endif
        </span>

        <svg class="credit-card__mark" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M3 8.5A2.5 2.5 0 0 1 5.5 6H18a3 3 0 0 1 3 3v7a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3V8.5Z"
                stroke="currentColor" stroke-width="1.6" />
            <path d="M3.5 7 15 3.2a1.4 1.4 0 0 1 1.8 1l.6 2.2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
            <circle cx="16.5" cy="12.5" r="1.6" fill="currentColor" />
        </svg>
    </div>

    @if ($ccUser->plan_id)
    <div class="credit-card__plan">
        <span class="credit-card__plan-name">{{ $ccUser->plan?->name ?? 'پلن فعال' }}</span>

        @if ($ccPlanExpires === null)
        <span class="credit-card__plan-days">بدون محدودیت زمانی</span>
        @elseif ($ccPlanDays < 0)
            <span class="credit-card__plan-days is-expired">منقضی شده</span>
            <!-- <span class="credit-card__plan-date">{{ jalali_date($ccPlanExpires) }}</span> -->
            @elseif ($ccPlanDays === 0)
            <span class="credit-card__plan-days is-soon">امروز منقضی می‌شود</span>
            @else
            <span class="credit-card__plan-days{{ $ccPlanDays <= 7 ? ' is-soon' : '' }}">{{ number_format($ccPlanDays) }} روز مانده</span>
            <!-- <span class="credit-card__plan-date">تا {{ jalali_date($ccPlanExpires) }}</span> -->
            @endif
    </div>
    @endif

    <div class="credit-card__foot">
        @if (Route::has('dashboard.wallet'))
        <a href="{{ route('dashboard.wallet') }}">شارژ کیف پول</a>
        @endif
        @if (Route::has('dashboard.packages'))
        <a href="{{ route('dashboard.packages') }}">خرید بسته</a>
        @endif
    </div>
</div>
@endif