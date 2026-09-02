<?php

/*
|--------------------------------------------------------------------------
| SMS provider (docs/starter.md §12 / §13)
|--------------------------------------------------------------------------
|
| The single place SMS delivery is wired up. The app only ever talks to
| App\Services\Sms\SmsProviderInterface, never a vendor SDK directly.
|
| Providers are listed in the registry below under an opaque internal
| codename. Nothing in this file, the .env keys, the logs or the rendered
| page names the real upstream vendor — customers must not be able to tell
| which provider powers the service. The brand-neutral 'label' is what the
| UI shows wherever the provider is mentioned; it follows the same
| three-layer cascade as config('theme.*') (file default -> .env ->
| `sms_provider_label` setting, overlaid in AppServiceProvider).
|
*/

return [

    // Active provider, by codename (see 'providers'). "log" is the
    // credential-free dev/test default (writes to the log, mirrors
    // PAYMENT_DRIVER=local).
    'provider' => env('SMS_PROVIDER', 'log'),

    // Brand-neutral name shown to customers wherever the SMS backend is
    // referenced. Change it from the admin panel (تنظیمات) — never expose
    // the real vendor name.
    'label' => env('SMS_PROVIDER_LABEL', 'سامانه پیامک'),

    // Where "user + admin" operation notifications are sent (docs/starter.md §44).
    'admin_mobile' => env('ADMIN_MOBILE'),

    // Provider registry: codename => driver class + credentials. Codenames
    // are deliberately non-descriptive so no vendor identity reaches config
    // keys, env vars, logs or page source.
    'providers' => [

        'log' => [
            'driver' => App\Services\Sms\LogProvider::class,
        ],

        'null' => [
            'driver' => App\Services\Sms\NullProvider::class,
        ],

        'pasargad' => [
            'driver' => App\Services\Sms\PasargadProvider::class,
            'username' => env('SMS_PASARGAD_USERNAME'),
            'password' => env('SMS_PASARGAD_PASSWORD'),
            'api_key' => env('SMS_PASARGAD_API_KEY'),
            'sender' => env('SMS_PASARGAD_SENDER'),
        ],

    ],

];
