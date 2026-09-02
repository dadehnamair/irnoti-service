<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * A plan purchase (docs/starter.md §8 / §51). Free plans are created already
 * `active`; paid plans move pending → awaiting_payment → active via the gateway
 * callback. Route key is the unguessable `token`, like {@see LineOrder}.
 */
class Subscription extends Model
{
    protected $fillable = [
        'token',
        'user_id',
        'plan_id',
        'plan_name',
        'billing_period',
        'price',
        'status',
        'admin_note',
        'transaction_id',
        'reference_id',
        'payment_driver',
        'paid_at',
        'starts_at',
        'expires_at',
    ];

    protected $casts = [
        'price' => 'integer',
        'paid_at' => 'datetime',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public const STATUSES = [
        'pending' => 'در انتظار بررسی',
        'awaiting_payment' => 'در انتظار پرداخت',
        'paid' => 'پرداخت شده',
        'active' => 'فعال',
        'cancelled' => 'لغو شده',
        'expired' => 'منقضی شده',
    ];

    public const BILLING_PERIODS = [
        'monthly' => 'ماهانه',
        'yearly' => 'سالانه',
    ];

    protected static function booted(): void
    {
        static::creating(function (Subscription $subscription) {
            if (blank($subscription->token)) {
                $subscription->token = Str::lower(Str::random(24));
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'token';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? (string) $this->status;
    }

    public function getBillingPeriodLabelAttribute(): string
    {
        return self::BILLING_PERIODS[$this->billing_period] ?? (string) $this->billing_period;
    }

    /** Still owes money and can be sent to the gateway. */
    public function isPayable(): bool
    {
        return $this->price > 0
            && in_array($this->status, ['pending', 'awaiting_payment'], true);
    }

    public function isFree(): bool
    {
        return (int) $this->price === 0;
    }
}
