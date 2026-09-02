<?php

use App\Models\Setting;
use Illuminate\Support\Carbon;
use Morilog\Jalali\Jalalian;

if (! function_exists('jalali_date')) {
    /**
     * Format a date as Jalali (Shamsi). Used everywhere a date is shown to the
     * user — public site, customer dashboard and the Filament admin tables.
     * Accepts a Carbon instance, a date string, a timestamp, or null.
     *
     * NB: the package ships its own global jdate() (returns a Jalalian object),
     * so this helper is named differently; the @jdate Blade directive calls it.
     */
    function jalali_date(mixed $date, string $format = 'Y/m/d', string $default = '—'): string
    {
        if (blank($date)) {
            return $default;
        }

        try {
            $carbon = $date instanceof DateTimeInterface
                ? Carbon::instance($date)
                : (is_numeric($date) ? Carbon::createFromTimestamp($date) : Carbon::parse($date));

            return Jalalian::fromCarbon($carbon)->format($format);
        } catch (Throwable) {
            return $default;
        }
    }
}

if (! function_exists('jalali_datetime')) {
    /** Jalali date + 24h time, e.g. 1405/06/11 14:05. */
    function jalali_datetime(mixed $date, string $default = '—'): string
    {
        return jalali_date($date, 'Y/m/d H:i', $default);
    }
}

if (! function_exists('toman')) {
    /**
     * Money for display: integer Toman, grouped in threes with commas
     * (e.g. 1,250,000). Never introduces a float.
     */
    function toman(mixed $amount, bool $withUnit = false): string
    {
        $formatted = number_format((int) $amount);

        return $withUnit ? $formatted.' تومان' : $formatted;
    }
}

if (! function_exists('normalize_mobile')) {
    /**
     * Normalise an Iranian mobile number to the local "09xxxxxxxxx" form the SMS
     * gateway and the provider phonebook expect. Leaves anything it doesn't
     * recognise untouched. Mirrors App\Services\Sms\SmsManager::normalize().
     */
    function normalize_mobile(mixed $number): string
    {
        $number = (string) $number;
        $digits = preg_replace('/\D+/', '', $number) ?? '';

        if (str_starts_with($digits, '0098')) {
            $digits = substr($digits, 4);
        } elseif (str_starts_with($digits, '98') && strlen($digits) === 12) {
            $digits = substr($digits, 2);
        }

        if (strlen($digits) === 10 && str_starts_with($digits, '9')) {
            $digits = '0'.$digits;
        }

        return $digits !== '' ? $digits : $number;
    }
}

if (! function_exists('rial_to_toman')) {
    /**
     * Convert a Rial amount to integer Toman for display. Some upstreams (the
     * SMS panel API, GetUserCredit2) only speak Rial; the whole UI shows
     * Toman, so every such value passes through here first. Integer division
     * — never a float, never a fractional Toman.
     */
    function rial_to_toman(mixed $rial): int
    {
        return intdiv((int) $rial, 10);
    }
}

if (! function_exists('sms_provider_label')) {
    /**
     * Brand-neutral name for the SMS backend, shown to customers wherever the
     * provider is mentioned. The real vendor name is never surfaced — this
     * resolves through the same cascade as config('theme.*'): the
     * `sms_provider_label` setting (admin panel) wins, else config('sms.label').
     */
    function sms_provider_label(): string
    {
        try {
            $label = Setting::get('sms_provider_label');
        } catch (Throwable) {
            $label = null;
        }

        return (string) ($label ?: config('sms.label', 'سامانه پیامک'));
    }
}
