<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Credential-free driver for local dev & staging (SMS_PROVIDER=log). Writes the
 * message to the application log instead of hitting a gateway — the same idea as
 * the bundled "local" payment driver. Never use in production.
 */
class LogProvider implements SmsProviderInterface
{
    public function send(string $to, string $message, ?string $from = null): ?string
    {
        $id = (string) Str::uuid();

        Log::channel(config('logging.default'))->info('[sms:log] send', [
            'id' => $id,
            'to' => $to,
            'from' => $from,
            'message' => $message,
        ]);

        return $id;
    }

    public function sendPattern(string $to, string $bodyId, array $variables): ?string
    {
        $id = (string) Str::uuid();

        Log::channel(config('logging.default'))->info('[sms:log] pattern', [
            'id' => $id,
            'to' => $to,
            'bodyId' => $bodyId,
            'variables' => $variables,
        ]);

        return $id;
    }

    public function deliveryStatus(string $recId): ?string
    {
        return 'delivered';
    }

    public function credit(): ?int
    {
        // A fake balance so the panel UI is usable without a real gateway.
        return 1000;
    }
}
