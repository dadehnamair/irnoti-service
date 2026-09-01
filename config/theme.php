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
    'phone' => env('THEME_PHONE', '+989123456789'),
    'phone_display' => '۰۹۱۲ ۳۴۵ ۶۷۸۹',

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
        ['label' => 'تعرفه‌ها', 'href' => '#pricing'],
        ['label' => 'خطوط اختصاصی', 'href' => '#lines'],
        ['label' => 'مستندات API', 'href' => '/developers'],
        ['label' => 'سوالات متداول', 'href' => '#faq'],
    ],

    'social' => [
        // 'instagram' => 'https://instagram.com/...',
        // 'linkedin' => 'https://linkedin.com/company/...',
    ],
];
