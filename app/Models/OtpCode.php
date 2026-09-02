<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

/**
 * One-time code for mobile verification / OTP login (docs/starter.md §26/§27).
 * The code is only ever stored hashed; the plaintext is returned once from
 * {@see self::issue()} so it can be texted to the user.
 */
class OtpCode extends Model
{
    protected $fillable = [
        'mobile', 'code', 'purpose', 'attempts', 'expires_at', 'last_sent_at', 'consumed_at',
    ];

    protected $casts = [
        'attempts' => 'integer',
        'expires_at' => 'datetime',
        'last_sent_at' => 'datetime',
        'consumed_at' => 'datetime',
    ];

    public const TTL_MINUTES = 5;

    public const RESEND_SECONDS = 90;

    public const MAX_ATTEMPTS = 5;

    public const PURPOSES = ['register', 'login'];

    public static function generateCode(): string
    {
        return str_pad((string) random_int(0, 99999), 5, '0', STR_PAD_LEFT);
    }

    /**
     * Create a fresh code for this mobile/purpose.
     *
     * @return array{0: string, 1: self} [plaintext code, model]
     */
    public static function issue(string $mobile, string $purpose = 'register'): array
    {
        $plain = self::generateCode();

        $otp = self::create([
            'mobile' => $mobile,
            'code' => Hash::make($plain),
            'purpose' => $purpose,
            'expires_at' => now()->addMinutes(self::TTL_MINUTES),
            'last_sent_at' => now(),
        ]);

        return [$plain, $otp];
    }

    /** Latest still-usable code for this mobile/purpose, if any. */
    public static function active(string $mobile, string $purpose = 'register'): ?self
    {
        return self::query()
            ->where('mobile', $mobile)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();
    }

    /** Seconds the user must still wait before a new code can be sent. */
    public function secondsUntilResend(): int
    {
        if (! $this->last_sent_at) {
            return 0;
        }

        $elapsed = (int) $this->last_sent_at->diffInSeconds(now(), true);

        return max(0, self::RESEND_SECONDS - $elapsed);
    }

    /** Check a submitted code; consumes the row on success, counts the attempt either way. */
    public function verify(string $code): bool
    {
        if ($this->consumed_at || $this->expires_at->isPast() || $this->attempts >= self::MAX_ATTEMPTS) {
            return false;
        }

        $this->increment('attempts');

        if (! Hash::check($code, $this->code)) {
            return false;
        }

        $this->forceFill(['consumed_at' => now()])->save();

        return true;
    }
}
