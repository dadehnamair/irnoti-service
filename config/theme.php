<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Brand
    |--------------------------------------------------------------------------
    |
    | Single source of truth for the public site identity. Nothing here should
    | be hard-coded inside Blade views — see docs/starter.md §67 and §81.
    | Later these values can be moved into a `settings` table and this file
    | can read from there without touching the views.
    |
    */

    'brand' => env('APP_NAME', 'irnoti'),
    'tagline' => 'پنل پیامک حرفه‌ای برای کسب‌وکارها و فروشگاه‌های آنلاین',

    /*
    |--------------------------------------------------------------------------
    | Colors
    |--------------------------------------------------------------------------
    |
    | The primary color drives the entire public site. Change THEME_PRIMARY in
    | .env (or this default) and every button, gradient, glow and focus ring
    | follows — the CSS derives all shades from it with color-mix().
    |
    | `accent` is the gradient partner of the primary (used on buttons / hero).
    | `secondary` stays green on purpose: it carries "online / success / active"
    | semantics and must not turn red with the brand color.
    |
    */

    'primary' => env('THEME_PRIMARY', '#ff3000'),
    'accent' => env('THEME_ACCENT', '#ff8a3d'),
    'secondary' => env('THEME_SECONDARY', '#0bc5a3'),

    /*
    |--------------------------------------------------------------------------
    | Contact
    |--------------------------------------------------------------------------
    */

    'email' => env('THEME_EMAIL', 'hello@irnoti.com'),
    'phone' => env('THEME_PHONE', '+982191016838'),
    'phone_display' => '021-91016838',
    'address' => env('THEME_ADDRESS', ''),

    /*
    |--------------------------------------------------------------------------
    | SEO
    |--------------------------------------------------------------------------
    */

    'seo' => [
        'title' => 'irnoti | پنل پیامک و خدمات ارسال پیامک حرفه‌ای',
        'description' => 'irnoti سرویس حرفه‌ای ارسال پیامک، پنل پیامکی، خطوط اختصاصی و API برای کسب‌وکارها و فروشگاه‌ها. پیامک انبوه، گزارش لحظه‌ای و پشتیبانی ۲۴/۷.',
        'keywords' => 'ارسال پیامک, پنل پیامکی, پیامک انبوه, خطوط اختصاصی, API پیامک, irnoti, خدمات پیامکی, ارسال پیامک کسب‌وکار',
        'url' => env('APP_URL', 'https://irnoti.com'),
        'image' => '/logo/logo-text200-30.png',
        'locale' => 'fa_IR',
    ],

    /*
    |--------------------------------------------------------------------------
    | Public navigation
    |--------------------------------------------------------------------------
    */

    'nav' => [
        ['label' => 'امکانات', 'href' => '#features'],
        ['label' => 'تعرفه‌ها', 'href' => '/pricing'],
        ['label' => 'خطوط اختصاصی', 'href' => '/lines'],
        ['label' => 'کد دستوری USSD', 'href' => '/ussd'],
        ['label' => 'بازارچه', 'href' => '/marketplace'],
        ['label' => 'وبلاگ', 'href' => '/blog'],
        ['label' => 'مستندات API', 'href' => '/developers'],
    ],

    'social' => [
        // 'instagram' => 'https://instagram.com/...',
        // 'linkedin' => 'https://linkedin.com/company/...',
    ],

    /*
    |--------------------------------------------------------------------------
    | Footer — docs/starter.md §67/§81
    |--------------------------------------------------------------------------
    |
    | Link columns rendered in the public-site footer (partials/site-footer).
    | Kept here (not hard-coded in Blade) so copy/links can later move into the
    | settings table. `trust` lists the Iranian e-commerce seals; `image` is a
    | local placeholder until the real badge markup/scripts are pasted in
    | (enamad + samandehi ship an <a><img></a> snippet — swap `html` in then).
    |
    */

    'footer' => [
        'about' => 'پلتفرم حرفه‌ای ارسال پیامک انبوه، خطوط اختصاصی و وب‌سرویس پیامک برای کسب‌وکارها و فروشگاه‌های آنلاین. گزارش لحظه‌ای، پشتیبانی ۲۴/۷ و API استاندارد.',
        'columns' => [
            [
                'title' => 'محصولات',
                'links' => [
                    ['label' => 'پنل پیامک', 'href' => '/#features'],
                    ['label' => 'تعرفه‌ها و پلن‌ها', 'href' => '/pricing'],
                    ['label' => 'خطوط اختصاصی', 'href' => '/lines'],
                    ['label' => 'کد دستوری USSD', 'href' => '/ussd'],
                    ['label' => 'بازارچه', 'href' => '/marketplace'],
                    ['label' => 'بسته‌های پیامکی', 'href' => '/pricing#packages'],
                    ['label' => 'وب‌سرویس و API', 'href' => '/developers'],
                ],
            ],
            [
                'title' => 'شرکت',
                'links' => [
                    ['label' => 'درباره ما', 'href' => '/#about'],
                    ['label' => 'وبلاگ', 'href' => '/blog'],
                    ['label' => 'سوالات متداول', 'href' => '/#faq'],
                    ['label' => 'تماس با ما', 'href' => '/#cta'],
                ],
            ],
            [
                'title' => 'راهنما و پشتیبانی',
                'links' => [
                    ['label' => 'مستندات فنی', 'href' => '/developers'],
                    ['label' => 'قوانین و مقررات', 'href' => '/terms'],
                    ['label' => 'حریم خصوصی', 'href' => '/privacy'],
                    ['label' => 'ورود به پنل', 'href' => '/login'],
                ],
            ],
        ],
    ],

    'trust' => [
        [
            'key' => 'enamad',
            'label' => 'نماد اعتماد الکترونیکی',
            'href' => 'https://www.enamad.ir/',
            'image' => '/trust/enamad.svg',
            'html' => null, // paste the eNamad <a id="..."><img ...></a> snippet here
        ],
        [
            'key' => 'samandehi',
            'label' => 'نماد ساماندهی',
            'href' => 'https://logo.samandehi.ir/',
            'image' => '/trust/samandehi.svg',
            'html' => null, // paste the Samandehi snippet here
        ],
        [
            'key' => 'kasbokar',
            'label' => 'اتحادیه کسب‌وکارهای مجازی',
            'href' => 'https://ecunion.ir/',
            'image' => '/trust/kasbokar.svg',
            'html' => null,
        ],
    ],
];
