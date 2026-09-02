@extends('layouts.account')

@section('title', 'تکمیل خرید پلن')

@section('content')
    <div class="account-card">
        <h2>تکمیل خرید پلن {{ $plan->name }}</h2>


        @unless ($plan->isFree())
            <div class="period-switch" role="group" aria-label="دورهٔ صورت‌حساب">
                <a href="{{ route('dashboard.plan.checkout', ['plan' => $plan->slug, 'period' => 'monthly']) }}"
                    @class(['is-active' => $period === 'monthly'])>ماهانه</a>
                <a href="{{ route('dashboard.plan.checkout', ['plan' => $plan->slug, 'period' => 'yearly']) }}"
                    @class(['is-active' => $period === 'yearly'])>سالانه</a>
            </div>
        @endunless

        <div class="account-stat-grid">
            <div class="account-stat">
                <span>پلن</span>
                <strong>{{ $plan->name }}</strong>
            </div>
            <div class="account-stat">
                <span>دوره</span>
                <strong>{{ $period === 'yearly' ? 'سالانه' : 'ماهانه' }}</strong>
            </div>
            <div class="account-stat">
                <span>مبلغ</span>
                <strong>
                    @if ($price > 0)
                        {{ number_format($price) }} تومان
                    @else
                        رایگان
                    @endif
                </strong>
            </div>
        </div>

        @if ($plan->feature_list)
            <ul class="checkout-features" style="margin-top:16px">
                @foreach ($plan->feature_list as $feature)
                    <li>{{ $feature }}</li>
                @endforeach
            </ul>
        @endif

        <form method="POST" action="{{ route('subscriptions.order') }}" style="margin-top:20px">
            @csrf
            <input type="hidden" name="plan" value="{{ $plan->slug }}" />
            <input type="hidden" name="period" value="{{ $period }}" />

            <button type="submit" class="btn btn-primary full">
                @if ($price > 0 && $onlinePayment)
                    پرداخت و فعال‌سازی
                @elseif ($price > 0)
                    ثبت درخواست پلن
                @else
                    فعال‌سازی رایگان
                @endif
            </button>

            <p class="auth-sub" style="margin-top:10px; text-align:center">
                @if ($price > 0 && $onlinePayment)
                    پس از ثبت به درگاه پرداخت منتقل می‌شوید.
                @elseif ($price > 0)
                    خرید آنلاین پلن غیرفعال است؛ کارشناسان برای هماهنگی پرداخت تماس می‌گیرند.
                @else
                    این پلن رایگان است و بلافاصله ثبت می‌شود.
                @endif
            </p>
            <p class="auth-sub" style="text-align:center">
                فعال‌سازی نهایی امکانات پس از تأیید حساب توسط کارشناسان انجام می‌شود.
            </p>
        </form>

        <a class="checkout-back" href="{{ route('dashboard.plans') }}">بازگشت به فهرست پلن‌ها</a>
    </div>
@endsection
