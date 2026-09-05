@extends('layouts.account')

@section('title', 'بازارچه')

@section('content')
<div class="account-card">
    <h2>بازارچه</h2>
    <p class="auth-sub">
        افزونه‌های کسب‌وکار را به سامانه اضافه کنید — از اتصال به سرویس‌هایی مثل ایرپلاس تا کارت ویزیت و منشی پیامکی.
    </p>
</div>

@php $anyApp = collect($groups)->flatten(1)->isNotEmpty(); @endphp

@unless ($anyApp)
<div class="account-card">
    <p class="auth-sub">در حال حاضر افزونه‌ای برای نصب تعریف نشده است.</p>
</div>
@endunless

@foreach ($groups as $category => $apps)
<div class="account-card">
    <h3>{{ $categories[$category] ?? $category }}</h3>

    <div class="plan-cards">
        @foreach ($apps as $app)
        @php $mine = $installed->get($app->id); @endphp
        <div @class(['plan-card', 'is-featured'=> $app->is_featured])>
            @if ($app->is_featured)
            <span class="plan-card__badge">پیشنهادی</span>
            @endif

            <h3>{{ $app->name }}</h3>
            @if ($app->vendor)
            <p class="plan-card__meta">ارائه‌دهنده: {{ $app->vendor }}</p>
            @endif
            @if ($app->tagline)
            <p class="plan-card__desc">{{ $app->tagline }}</p>
            @endif

            <p class="plan-card__price"><strong>{{ $app->price_label }}</strong></p>

            @if ($mine && $mine->status === 'active')
            <a class="btn btn-secondary full" href="{{ route('marketplace.manage', $mine) }}">مدیریت افزونه</a>
            @elseif ($mine)
            <a class="btn btn-primary full" href="{{ route('marketplace.manage', $mine) }}">ادامه فرایند نصب</a>
            @else
            <a class="btn btn-primary full" href="{{ route('marketplace.show', $app) }}">
                {{ $app->isFree() ? 'افزودن' : 'مشاهده و خرید' }}
            </a>
            @endif
        </div>
        @endforeach
    </div>
</div>
@endforeach
@endsection