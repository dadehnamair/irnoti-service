<?php

use App\Marketplace\Handlers\FeatureUnlockHandler;
use App\Marketplace\Handlers\IrPlusHandler;

/*
 * «بازارچه» wiring (docs/starter.md §15). `handlers` maps the opaque
 * key stored on a `marketplace_apps` row to the class that runs it — mirrors
 * config('sms.providers.*'). Per-integration blocks (irplus, …) carry the
 * driver switch + endpoint, exactly like the SMS provider registry: `fake` is
 * the credential-free default for dev/tests, `http` talks to the real service.
 */

return [
    'handlers' => [
        'feature_unlock' => FeatureUnlockHandler::class,
        'irplus' => IrPlusHandler::class,
    ],

    'irplus' => [
        'driver' => env('MARKETPLACE_IRPLUS_DRIVER', 'fake'), // fake|http
        'base_url' => env('MARKETPLACE_IRPLUS_BASE_URL', 'https://api.irplus.ir'),
        'timeout' => 15,
    ],
];
