<?php

namespace App\Services\Sms;

use App\Models\Setting;
use Illuminate\Support\Str;

/**
 * The single entry point the rest of the app uses for SMS (docs/starter.md §12).
 * Wraps whichever {@see SmsProviderInterface} is bound in the container and adds
 * app-level concerns: number normalisation, the configured sender line, and the
 * "notify admin" address for operation notifications (docs/starter.md §44).
 */
class SmsManager
{
    /**
     * @param  string|null  $senderOverride  Sender line to use instead of the
     *                                       configured one — set when the manager
     *                                       wraps a customer's own panel ({@see UserSmsGateway}).
     */
    public function __construct(
        private readonly SmsProviderInterface $provider,
        private readonly ?string $senderOverride = null,
    ) {}

    public function send(string $to, string $message): ?string
    {
        return $this->provider->send($this->normalize($to), $message, $this->sender());
    }

    /** @param  array<int, string>  $variables */
    public function sendPattern(string $to, string $bodyId, array $variables): ?string
    {
        return $this->provider->sendPattern($this->normalize($to), $bodyId, $variables);
    }

    public function deliveryStatus(string $recId): ?string
    {
        return $this->provider->deliveryStatus($recId);
    }

    /** Remaining panel credit (SMS count), or null when the driver can't report it. */
    public function credit(): ?int
    {
        return $this->provider->credit();
    }

    public function sender(): ?string
    {
        return $this->senderOverride ?: config('services.sms.melipayamak.sender');
    }

    /** Mobile that receives the admin side of operation notifications (docs/starter.md §44). */
    public function adminMobile(): ?string
    {
        return Setting::get('admin_mobile') ?: config('services.sms.admin_mobile');
    }

    /**
     * Normalise Iranian mobile numbers to the local "09xxxxxxxxx" form the
     * gateway expects. Leaves anything it doesn't recognise untouched.
     */
    public function normalize(string $number): string
    {
        $digits = preg_replace('/\D+/', '', $number) ?? '';

        if (Str::startsWith($digits, '0098')) {
            $digits = substr($digits, 4);
        } elseif (Str::startsWith($digits, '98') && strlen($digits) === 12) {
            $digits = substr($digits, 2);
        }

        if (strlen($digits) === 10 && Str::startsWith($digits, '9')) {
            $digits = '0'.$digits;
        }

        return $digits !== '' ? $digits : $number;
    }
}
