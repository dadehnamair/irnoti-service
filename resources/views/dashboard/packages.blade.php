@extends('layouts.account')

@section('title', 'بسته‌های پیامکی')

@section('content')
    <div class="account-card">
        <h2>بسته‌های پیامکی</h2>
        <p class="auth-sub">
            اعتبار پیامکی فعلی شما: <strong>@toman($user->sms_credit) پیامک</strong> — موجودی کیف پول:
            <strong>@toman($walletBalance) تومان</strong>
        </p>
    </div>

    @if ($packages->isEmpty())
        <div class="account-card"><p class="auth-sub">در حال حاضر بسته‌ای برای فروش تعریف نشده است.</p></div>
    @else
        <div class="plan-cards">
            @foreach ($packages as $package)
                <div @class(['plan-card', 'is-featured' => $package->is_featured])>
                    @if ($package->badge_label)
                        <span class="plan-card__badge">{{ $package->badge_label }}</span>
                    @endif
                    <h3>{{ $package->name }}</h3>
                    <p class="plan-card__price">
                        @if ($package->compare_at_price)
                            <del>@toman($package->compare_at_price)</del>
                        @endif
                        <strong>@toman($package->price)</strong> تومان
                    </p>
                    <p class="plan-card__meta">@toman($package->sms_count) پیامک — هر پیامک {{ number_format($package->unit_price) }} تومان</p>
                    @if ($package->description)
                        <p class="plan-card__desc">{{ $package->description }}</p>
                    @endif
                    <a class="btn btn-primary full" href="{{ route('dashboard.packages.checkout', $package) }}">خرید بسته</a>
                </div>
            @endforeach
        </div>
    @endif
@endsection
