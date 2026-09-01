@php
    $brand = config('theme.brand');
    $seo = config('theme.seo');
    $primary = config('theme.primary');
    $email = config('theme.email');
    $phoneDisplay = config('theme.phone_display');
    $url = rtrim($seo['url'], '/');
    $canonical = route('lines');

    $metaTitle = 'خرید خط اختصاصی پیامک ' . $brand . ' | خطوط ۱۰۰۰، ۲۰۰۰، ۳۰۰۰ و ۰۲۱';
    $metaDescription = 'لیست خطوط اختصاصی پیامک ' . $brand
        . ' با پیش‌شماره‌های ۱۰۰۰، ۲۰۰۰، ۳۰۰۰، ۵۰۰۰، ۰۲۱ و... — انتخاب تعداد ارقام، مشاهده قیمت و ثبت سفارش آنلاین.';

    $totalLines = $groups->sum(fn ($g) => $g['lines']->count());

    $graph = [
        [
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'خانه', 'item' => $url . '/'],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'خطوط اختصاصی', 'item' => $canonical],
            ],
        ],
    ];

    foreach ($groups as $group) {
        foreach ($group['lines'] as $line) {
            $graph[] = [
                '@type' => 'Product',
                'name' => 'خط ' . $line->prefix . ' (' . $line->digits . ' رقمی) ' . $brand,
                'description' => $line->description ?: ('خط اختصاصی پیامک با پیش‌شماره ' . $line->prefix),
                'brand' => ['@type' => 'Brand', 'name' => $brand],
                'offers' => [
                    '@type' => 'Offer',
                    'price' => $line->price,
                    'priceCurrency' => 'IRR',
                    'availability' => $line->sale_status === 'available'
                        ? 'https://schema.org/InStock'
                        : 'https://schema.org/OutOfStock',
                    'url' => $canonical,
                ],
            ];
        }
    }

    $jsonLd = json_encode(
        ['@context' => 'https://schema.org', '@graph' => $graph],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
@endphp
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1" />
    <meta name="theme-color" content="{{ $primary }}" />
    <meta name="author" content="{{ $brand }}" />
    <meta name="description" content="{{ $metaDescription }}" />

    <meta property="og:type" content="website" />
    <meta property="og:site_name" content="{{ $brand }}" />
    <meta property="og:locale" content="{{ $seo['locale'] }}" />
    <meta property="og:title" content="{{ $metaTitle }}" />
    <meta property="og:description" content="{{ $metaDescription }}" />
    <meta property="og:url" content="{{ $canonical }}" />
    <meta property="og:image" content="{{ $url }}{{ $seo['image'] }}" />
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="{{ $metaTitle }}" />
    <meta name="twitter:description" content="{{ $metaDescription }}" />
    <meta name="twitter:image" content="{{ $url }}{{ $seo['image'] }}" />

    <link rel="canonical" href="{{ $canonical }}" />
    <link rel="icon" href="/logo/favicon.png" type="image/png" />

    <title>{{ $metaTitle }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800;900&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ route('theme.css') }}" />

    <script type="application/ld+json">
        @php echo $jsonLd;
        @endphp
    </script>
</head>

<body>
    <div class="page-shell">
        @include('partials.site-header')

        <main id="top">
            <section class="section pricing-hero">
                <div class="container">
                    <nav class="blog-breadcrumb" aria-label="مسیر">
                        <a href="{{ route('home') }}">خانه</a>
                        <span aria-hidden="true">/</span>
                        <span>خطوط اختصاصی</span>
                    </nav>

                    <div class="section-heading center">
                        <span class="kicker">خطوط اختصاصی</span>
                        <h1>خط اختصاصی پیامک خود را انتخاب و خریداری کنید</h1>
                        <p>{{ number_format($totalLines) }} خط فعال با پیش‌شماره‌های ۱۰۰۰، ۲۰۰۰، ۳۰۰۰، ۵۰۰۰، ۰۲۱ و... — تعداد ارقام و نوع خط را انتخاب کنید، قیمت را ببینید و سفارش را ثبت کنید.</p>
                    </div>

                    @if ($groups->isEmpty())
                        <p class="pricing-empty">به‌زودی خطوط اختصاصی در این بخش نمایش داده می‌شوند.</p>
                    @else
                        <div class="line-tabs" role="tablist" aria-label="پیش‌شماره خطوط">
                            <button class="line-tab active" type="button" role="tab" aria-selected="true" data-prefix="all">همه خطوط</button>
                            @foreach ($groups as $group)
                                <button class="line-tab" type="button" role="tab" aria-selected="false" data-prefix="{{ $group['prefix'] }}">{{ $group['label'] }}</button>
                            @endforeach
                        </div>

                        <div class="line-filters">
                            <label>
                                <span>تعداد ارقام</span>
                                <select id="filter-digits">
                                    <option value="all">همه</option>
                                    @foreach ($digitOptions as $d)
                                        <option value="{{ $d }}">{{ $d }} رقمی</option>
                                    @endforeach
                                </select>
                            </label>
                            <label>
                                <span>نوع خط</span>
                                <select id="filter-type">
                                    <option value="all">همه</option>
                                    @foreach ($typeOptions as $t)
                                        <option value="{{ $t }}">{{ \App\Models\SmsLine::TYPES[$t] ?? $t }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="line-filter-check">
                                <input type="checkbox" id="filter-rond" />
                                <span>فقط شماره‌های رند</span>
                            </label>
                        </div>

                        <div class="line-cards" id="line-cards">
                            @foreach ($groups as $group)
                                @foreach ($group['lines'] as $line)
                                    <article class="line-card"
                                        data-prefix="{{ $line->prefix }}"
                                        data-digits="{{ $line->digits }}"
                                        data-type="{{ $line->line_type }}"
                                        data-rond="{{ $line->is_rond ? '1' : '0' }}">
                                        <header class="line-card-head">
                                            <span class="line-card-num">{{ $line->display_number }}</span>
                                            <span class="line-card-prefix">خطوط {{ $line->prefix }}</span>
                                        </header>

                                        <ul class="line-card-meta">
                                            <li>{{ $line->digits }} رقمی</li>
                                            <li>{{ $line->type_label }}</li>
                                            @if ($line->operator)<li>{{ $line->operator }}</li>@endif
                                            @if ($line->is_rond)<li class="is-rond">رند</li>@endif
                                        </ul>

                                        @if ($line->feature_list)
                                            <ul class="line-card-features">
                                                @foreach (array_slice($line->feature_list, 0, 3) as $feature)
                                                    <li>{{ $feature }}</li>
                                                @endforeach
                                            </ul>
                                        @endif

                                        <div class="line-card-foot">
                                            @if ($line->requires_inquiry)
                                                <span class="line-card-price inquiry">استعلام قیمت</span>
                                            @else
                                                <span class="line-card-price">
                                                    @if ($line->compare_at_price)
                                                        <del>{{ number_format($line->compare_at_price) }}</del>
                                                    @endif
                                                    <strong>{{ number_format($line->price) }}</strong>
                                                    <span class="unit">تومان</span>
                                                </span>
                                            @endif

                                            @if ($line->sale_status === 'available')
                                                <button class="btn btn-primary line-buy"
                                                    type="button"
                                                    data-line-id="{{ $line->id }}"
                                                    data-line-label="خطوط {{ $line->prefix }} — {{ $line->display_number }} ({{ $line->digits }} رقمی)"
                                                    data-line-price="{{ $line->requires_inquiry ? 'استعلام' : number_format($line->price) . ' تومان' }}">
                                                    {{ $line->requires_inquiry ? 'درخواست استعلام' : 'خرید خط' }}
                                                </button>
                                            @else
                                                <span class="pill dark">{{ $line->sale_status_label }}</span>
                                            @endif
                                        </div>
                                    </article>
                                @endforeach
                            @endforeach
                        </div>

                        <p class="line-empty-state" id="line-empty-state" hidden>خطی با این فیلترها پیدا نشد. فیلترها را تغییر دهید.</p>
                    @endif
                </div>
            </section>

            <section class="section alt-bg" aria-labelledby="lines-help-title">
                <div class="container">
                    <div class="section-heading center">
                        <span class="kicker">راهنما</span>
                        <h2 id="lines-help-title">مراحل خرید خط اختصاصی</h2>
                    </div>
                    <div class="line-steps">
                        <div><span>۱</span><p>پیش‌شماره و تعداد ارقام دلخواه را انتخاب کنید.</p></div>
                        <div><span>۲</span><p>قیمت خط را ببینید و روی «خرید خط» بزنید.</p></div>
                        <div><span>۳</span><p>فرم اطلاعات تماس را تکمیل و سفارش را ثبت کنید.</p></div>
                        <div><span>۴</span><p>کارشناسان ما برای تکمیل پرداخت و فعال‌سازی با شما تماس می‌گیرند.</p></div>
                    </div>
                    <p class="line-help-contact">سوالی دارید؟ با پشتیبانی تماس بگیرید: <a href="tel:{{ config('theme.phone') }}">{{ $phoneDisplay }}</a> یا <a href="mailto:{{ $email }}">{{ $email }}</a></p>
                </div>
            </section>
        </main>

        @include('partials.site-footer')
    </div>

    <dialog class="line-dialog" id="order-dialog">
        <form method="POST" action="{{ route('lines.order') }}" class="line-order-form">
            @csrf
            <input type="hidden" name="sms_line_id" id="order-line-id" value="{{ old('sms_line_id') }}" />

            <div class="line-dialog-head">
                <h2>ثبت سفارش خط</h2>
                <button type="button" class="line-dialog-close" data-close aria-label="بستن">×</button>
            </div>

            <div class="line-order-summary">
                <span id="order-line-label">{{ old('sms_line_id') ? 'خط انتخابی' : '' }}</span>
                <strong id="order-line-price"></strong>
            </div>

            @if ($errors->any())
                <div class="line-order-errors">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="line-order-grid">
                <label>
                    <span>نام و نام خانوادگی <b>*</b></span>
                    <input type="text" name="customer_name" value="{{ old('customer_name') }}" required maxlength="120" />
                </label>
                <label>
                    <span>شماره موبایل <b>*</b></span>
                    <input type="tel" name="customer_phone" value="{{ old('customer_phone') }}" required maxlength="20" inputmode="tel" placeholder="09xxxxxxxxx" />
                </label>
                <label>
                    <span>ایمیل</span>
                    <input type="email" name="customer_email" value="{{ old('customer_email') }}" maxlength="160" />
                </label>
                <label>
                    <span>نام کسب‌وکار</span>
                    <input type="text" name="company" value="{{ old('company') }}" maxlength="160" />
                </label>
                <label>
                    <span>شماره دلخواه (اختیاری)</span>
                    <input type="text" name="desired_number" value="{{ old('desired_number') }}" maxlength="40" placeholder="مثلاً 30001234" />
                </label>
                <label class="line-order-full">
                    <span>توضیحات</span>
                    <textarea name="note" rows="3" maxlength="1000">{{ old('note') }}</textarea>
                </label>
            </div>

            <div class="line-order-actions">
                <button type="submit" class="btn btn-primary full">ثبت سفارش</button>
                <p>پس از ثبت، کد پیگیری دریافت می‌کنید. پرداخت به‌صورت هماهنگی با کارشناس انجام می‌شود.</p>
            </div>
        </form>
    </dialog>

    @if ($errors->any())
        <script>document.addEventListener('DOMContentLoaded', () => document.getElementById('order-dialog')?.showModal());</script>
    @endif
</body>

</html>
