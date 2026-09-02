<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

/**
 * One "شارژ حساب" request (docs/starter.md §23). Same shape as {@see Subscription}
 * / {@see LineOrder}: unguessable `token` route key, own payment columns. When it
 * settles, the wallet is credited exactly once (idempotency key = "topup:{id}").
 */
class WalletTopup extends Model
{
    protected $fillable = [
        'token',
        'user_id',
        'amount',
        'status',
        'method',
        'payment_driver',
        'transaction_id',
        'reference_id',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'integer',
        'paid_at' => 'datetime',
    ];

    public const STATUSES = [
        'awaiting_payment' => 'در انتظار پرداخت',
        'paid' => 'پرداخت شده',
        'cancelled' => 'لغو شده',
    ];

    protected static function booted(): void
    {
        static::creating(function (WalletTopup $topup) {
            if (blank($topup->token)) {
                $topup->token = Str::lower(Str::random(24));
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

    public function receipts(): MorphMany
    {
        return $this->morphMany(BankReceipt::class, 'receiptable');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? (string) $this->status;
    }

    public function isPayable(): bool
    {
        return $this->amount > 0 && $this->status === 'awaiting_payment';
    }
}
