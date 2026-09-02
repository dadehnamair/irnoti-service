<?php

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
            $carbon = $date instanceof \DateTimeInterface
                ? Carbon::instance($date)
                : (is_numeric($date) ? Carbon::createFromTimestamp($date) : Carbon::parse($date));

            return Jalalian::fromCarbon($carbon)->format($format);
        } catch (\Throwable) {
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
