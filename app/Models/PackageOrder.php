<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

/**
 * A purchase of an {@see SmsPackage} (docs/starter.md §12). Same shape as
 * {@see Subscription}: `token` route key, snapshot columns, status workflow.
 * Settling it adds `sms_count` to users.sms_credit exactly once.
 */
class PackageOrder extends Model
{
    protected $fillable = [
        'token',
        'user_id',
        'sms_package_id',
        'package_name',
        'sms_count',
        'price',
        'status',
        'method',
        'payment_driver',
        'transaction_id',
        'reference_id',
        'paid_at',
    ];

    protected $casts = [
        'sms_count' => 'integer',
        'price' => 'integer',
        'paid_at' => 'datetime',
    ];

    public const STATUSES = [
        'pending' => 'در انتظار بررسی',
        'awaiting_payment' => 'در انتظار پرداخت',
        'paid' => 'پرداخت شده',
        'completed' => 'تکمیل شده',
        'cancelled' => 'لغو شده',
    ];

    protected static function booted(): void
    {
        static::creating(function (PackageOrder $order) {
            if (blank($order->token)) {
                $order->token = Str::lower(Str::random(24));
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

    public function package(): BelongsTo
    {
        return $this->belongsTo(SmsPackage::class, 'sms_package_id');
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
        return $this->price > 0
            && in_array($this->status, ['pending', 'awaiting_payment'], true);
    }
}
