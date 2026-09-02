@extends('layouts.account')

@section('title', 'پلن و اشتراک')

@section('content')
    <div class="account-card">
        <h2>انتخاب پلن</h2>

        @if (session('auth_status'))
            <p class="auth-note">{{ session('auth_status') }}</p>
        @endif

        @if ($user->plan)
            <p class="auth-sub">
                پلن فعلی شما: <strong>{{ $user->plan->name }}</strong>
                @if ($user->plan_expires_at)
                    — تا {{ $user->plan_expires_at->format('Y/m/d') }}
                @endif
            </p>
        @endif

        <div class="period-switch" role="group" aria-label="دورهٔ صورت‌حساب" data-period-switch>
            <button type="button" class="is-active" data-period="monthly">ماهانه</button>
            <button type="button" data-period="yearly">سالانه</button>
        </div>

        <div class="account-stat-grid" data-plan-grid>
            @forelse ($plans as $plan)
                @php($yearly = $plan->price_yearly ?? $plan->price_monthly * 12)
                <div class="account-stat">
                    <span>{{ $plan->name }}</span>
                    <strong class="plan-price"
                        data-monthly="{{ $plan->isFree() ? 'رایگان' : number_format($plan->price_monthly) . ' تومان / ماه' }}"
                        data-yearly="{{ $plan->isFree() ? 'رایگان' : number_format($yearly) . ' تومان / سال' }}">
                        @if ($plan->isFree())
                            رایگان
                        @else
                            {{ number_format($plan->price_monthly) }} تومان / ماه
                        @endif
                    </strong>

                    @if (! $plan->isFree() && $yearly < $plan->price_monthly * 12)
                        <span class="plan-saving" data-only="yearly">
                            صرفه‌جویی
                            {{ round((1 - $yearly / ($plan->price_monthly * 12)) * 100) }}٪
                        </span>
                    @endif

                    @if ($plan->description)
                        <p class="auth-sub" style="margin:8px 0 0">{{ $plan->description }}</p>
                    @endif

                    <div style="margin-top:12px">
                        @if ($user->plan_id === $plan->id)
                            <span class="account-badge is-ok">پلن فعلی</span>
                        @else
                            <a class="btn btn-primary plan-cta"
                                data-checkout="{{ route('dashboard.plan.checkout', ['plan' => $plan->slug]) }}"
                                href="{{ route('dashboard.plan.checkout', ['plan' => $plan->slug, 'period' => 'monthly']) }}">
                                {{ $plan->isFree() ? 'فعال‌سازی رایگان' : 'انتخاب و پرداخت' }}
                            </a>
                        @endif
                    </div>
                </div>
            @empty
                <p class="auth-sub">هنوز پلنی تعریف نشده است.</p>
            @endforelse
        </div>
    </div>
@endsection
