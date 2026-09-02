@extends('layouts.account')

@section('title', 'خلاصه حساب')

@section('content')
    @php($status = $user->status)
    @if ($status === 'pending')
        <div class="account-banner is-warn">
            اطلاعات حساب شما کامل نیست. برای فعال‌سازی امکانات، ابتدا اطلاعات را تکمیل کنید و یک پلن انتخاب کنید.
        </div>
    @elseif ($status === 'awaiting_approval')
        <div class="account-banner is-info">
            اطلاعات و پلن شما ثبت شده و حساب در <strong>انتظار تأیید کارشناسان</strong> است. پس از تأیید،
            ارسال پیامک و خرید خط برایتان فعال می‌شود.
        </div>
    @elseif ($user->isApproved())
        <div class="account-banner is-ok">حساب شما فعال است.</div>
    @endif

    <div class="account-card">
        <h2>خوش آمدید، {{ $user->full_name }}</h2>
        <div class="account-stat-grid">
            <div class="account-stat">
                <span>شماره موبایل</span>
                <strong dir="ltr">{{ $user->mobile }}</strong>
            </div>
            <div class="account-stat">
                <span>وضعیت حساب</span>
                <strong>{{ $user->status_label }}</strong>
            </div>
            <div class="account-stat">
                <span>تکمیل اطلاعات</span>
                <strong>
                    @if ($user->isProfileComplete())
                        <span class="account-badge is-ok">کامل</span>
                    @else
                        <span class="account-badge is-warn">ناقص</span>
                    @endif
                </strong>
            </div>
            <div class="account-stat">
                <span>وضعیت مدارک</span>
                <strong>
                    @php($docClass = match ($user->documents_status) {
                        'approved' => 'account-badge is-ok',
                        'rejected' => 'account-badge is-danger',
                        default => 'account-badge is-warn',
                    })
                    <span class="{{ $docClass }}">{{ $user->documents_status_label }}</span>
                </strong>
            </div>
            <div class="account-stat">
                <span>پلن فعال</span>
                <strong>{{ $user->plan?->name ?? '—' }}</strong>
            </div>
            @if ($user->plan_expires_at)
                <div class="account-stat">
                    <span>انقضای پلن</span>
                    <strong>@jdate($user->plan_expires_at)</strong>
                </div>
            @endif
            <div class="account-stat">
                <span>موجودی کیف پول</span>
                <strong>@toman($user->walletBalance()) تومان</strong>
            </div>
            <div class="account-stat">
                <span>اعتبار پیامکی</span>
                <strong>@toman($user->sms_credit) پیامک</strong>
            </div>
        </div>

        @if ($user->documents_status === 'rejected' && $user->documents_reject_reason)
            <p class="account-inline-note is-danger">
                دلیل رد مدارک: {{ $user->documents_reject_reason }} —
                <a href="{{ route('dashboard.profile.step', ['step' => 3]) }}">بارگذاری مجدد مدارک</a>
            </p>
        @endif
    </div>

    <div class="account-card">
        <h2>کیف پول و مالی</h2>
        <p class="auth-sub">حساب خود را شارژ کنید، بسته پیامکی بخرید یا صورت‌حساب‌ها را پرداخت کنید.</p>
        <div class="account-actions">
            <a class="btn btn-primary" href="{{ route('dashboard.wallet') }}">شارژ حساب</a>
            <a class="btn btn-secondary" href="{{ route('dashboard.packages') }}">خرید بسته پیامکی</a>
            <a class="btn btn-ghost" href="{{ route('dashboard.transactions') }}">سوابق مالی</a>
        </div>
    </div>

    @unless ($user->isProfileComplete())
        <div class="account-card">
            <h2>اطلاعات حساب خود را تکمیل کنید</h2>
            <p class="auth-sub">برای استفاده از همهٔ سرویس‌ها، اطلاعات هویتی و آدرس را در چند مرحله وارد کنید.</p>
            <a class="btn btn-primary" href="{{ route('dashboard.profile') }}">شروع تکمیل اطلاعات</a>
        </div>
    @endunless

    @if ($user->isApproved() && $user->hasSmsPanel())
        <div class="account-card">
            <h2>پنل پیامک</h2>
            <p class="auth-sub">اعتبار و ارسال تکی را از بخش «ارسال پیامک» مدیریت کنید.</p>
            <a class="btn btn-secondary" href="{{ route('dashboard.sms') }}">رفتن به ارسال پیامک</a>
        </div>
    @endif

    @if ($user->lineOrders()->exists())
        <div class="account-card">
            <h2>سفارش‌های خط من</h2>
            <ul class="account-list">
                @foreach ($user->lineOrders()->latest()->limit(5)->get() as $order)
                    <li>
                        <a href="{{ route('dashboard.lines.show', $order) }}">{{ $order->line_label }}</a>
                        <span class="account-badge is-warn">{{ $order->status_label }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
@endsection
