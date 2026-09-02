<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use RuntimeException;

/**
 * One immutable line in the financial ledger (docs/starter.md §22 / §50). Written
 * only through {@see Wallet::credit()} / {@see Wallet::debit()}; never updated or
 * deleted — the model blocks both.
 */
class WalletTransaction extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'wallet_id',
        'type',
        'direction',
        'amount',
        'balance_before',
        'balance_after',
        'reference_type',
        'reference_id',
        'description',
        'meta',
        'idempotency_key',
    ];

    protected $casts = [
        'amount' => 'integer',
        'balance_before' => 'integer',
        'balance_after' => 'integer',
        'meta' => 'array',
        'created_at' => 'datetime',
    ];

    /** Transaction type → Persian label (docs/starter.md §22 "انواع"). */
    public const TYPES = [
        'topup' => 'شارژ حساب',
        'plan_purchase' => 'خرید پلن',
        'line_purchase' => 'خرید خط',
        'package_purchase' => 'خرید بسته پیامکی',
        'invoice_payment' => 'پرداخت صورت‌حساب',
        'refund' => 'بازگشت وجه',
        'adjustment' => 'اصلاح دستی',
    ];

    public const DIRECTIONS = [
        'credit' => 'واریز',
        'debit' => 'برداشت',
    ];

    protected static function booted(): void
    {
        static::updating(function () {
            throw new RuntimeException('Wallet transactions are immutable.');
        });

        static::deleting(function () {
            throw new RuntimeException('Wallet transactions are immutable.');
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? (string) $this->type;
    }

    public function getDirectionLabelAttribute(): string
    {
        return self::DIRECTIONS[$this->direction] ?? (string) $this->direction;
    }

    public function getSignedAmountAttribute(): int
    {
        return $this->direction === 'debit' ? -$this->amount : $this->amount;
    }
}
