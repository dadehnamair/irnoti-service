<?php

/*
|--------------------------------------------------------------------------
| Messenger channels — bulk send to بله / ایتا / واتساپ (docs/starter.md §91)
|--------------------------------------------------------------------------
|
| A service parallel to config/sms.php: the app only ever talks to
| App\Services\Messenger\MessengerManager, never a channel SDK directly.
|
| `driver` picks the transport for every channel at once — "log" is the
| credential-free dev default (writes to the log, mirrors SMS_PROVIDER=log
| and PAYMENT_DRIVER=local), "null" is the no-op test driver, and "http"
| uses the real per-channel driver classes below. As with the SMS layer,
| nothing here, in the .env keys or in the logs should name the upstream
| aggregator that actually carries the message — only the destination
| network (بله/ایتا/واتساپ). Each channel's brand-neutral `label` follows
| the same cascade as config('theme.*'): file default -> .env -> the
| `messenger_<key>_label` setting, overlaid in AppServiceProvider.
|
*/

return [

    // Master switch. The `messenger_enabled` setting overlays this at runtime.
    'enabled' => env('MESSENGER_ENABLED', true),

    // Transport for every channel: "log" (dev), "null" (tests), "http" (prod).
    'driver' => env('MESSENGER_DRIVER', 'log'),

    // Channel registry: destination network => real driver class + capability
    // flag + per-recipient tariff (Toman) + credentials. `bulk` gates whether
    // the channel may be used for a group/list send at all.
    'channels' => [

        'bale' => [
            'driver' => App\Services\Messenger\Channels\BaleChannel::class,
            'label' => 'بله',
            'bulk' => true,
            'tariff' => (int) env('MESSENGER_BALE_TARIFF', 0),
            'token' => env('MESSENGER_BALE_TOKEN'),
            'base_url' => env('MESSENGER_BALE_BASE_URL', 'https://tapi.bale.ai'),
        ],

        'eitaa' => [
            'driver' => App\Services\Messenger\Channels\EitaaChannel::class,
            'label' => 'ایتا',
            'bulk' => true,
            'tariff' => (int) env('MESSENGER_EITAA_TARIFF', 0),
            'token' => env('MESSENGER_EITAA_TOKEN'),
            'base_url' => env('MESSENGER_EITAA_BASE_URL', 'https://eitaayar.ir/api'),
        ],

        'whatsapp' => [
            'driver' => App\Services\Messenger\Channels\WhatsAppChannel::class,
            'label' => 'واتساپ',
            'bulk' => true,
            'tariff' => (int) env('MESSENGER_WHATSAPP_TARIFF', 0),
            'token' => env('MESSENGER_WHATSAPP_TOKEN'),
            'phone_id' => env('MESSENGER_WHATSAPP_PHONE_ID'),
            'base_url' => env('MESSENGER_WHATSAPP_BASE_URL', 'https://graph.facebook.com/v21.0'),
        ],

    ],

];
