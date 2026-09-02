<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | SMS provider (docs/starter.md §12 / §13)
    |--------------------------------------------------------------------------
    |
    | The app talks to SMS through App\Services\Sms\SmsProviderInterface, never
    | a vendor SDK directly. "log" is the credential-free dev/test default
    | (writes to the log, mirrors PAYMENT_DRIVER=local); "melipayamak" is the
    | production driver. Credentials never live in code — only here from .env.
    |
    */

    'sms' => [
        'provider' => env('SMS_PROVIDER', 'log'),

        'melipayamak' => [
            'username' => env('MELIPAYAMAK_USERNAME'),
            'password' => env('MELIPAYAMAK_PASSWORD'),
            'api_key' => env('MELIPAYAMAK_API_KEY'),
            'sender' => env('MELIPAYAMAK_SENDER'),
        ],

        // Where "user + admin" operation notifications are sent (docs/starter.md §44).
        'admin_mobile' => env('ADMIN_MOBILE'),
    ],

];
