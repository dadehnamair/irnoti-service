<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * A customer-submitted proof of an offline bank transfer (docs/starter.md §22).
 * Sits alongside the online gateway as a payment method. `receiptable` is what is
 * being paid (null = a plain wallet top-up). Approval effects run once through
 * {@see App\Support\BankReceiptService}.
 */
class BankReceipt extends Model
{
    protected $fillable = [
        'user_id',
        'receiptable_type',
        'receiptable_id',
        'bank_account_id',
        'amount',
        'tracking_code',
        'transfer_type',
        'paid_at',
        'image_path',
        'status',
        'admin_note',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'amount' => 'integer',
        'paid_at' => 'date',
        'reviewed_at' => 'datetime',
    ];

    public const STATUSES = [
        'pending' => 'در انتظار بررسی',
        'approved' => 'تأیید شده',
        'rejected' => 'رد شده',
    ];

    public const TRANSFER_TYPES = [
        'paya' => 'پایا',
        'satna' => 'ساتنا',
        'card' => 'کارت به کارت',
        'pol' => 'پل (انتقال آنی)',
        'cash' => 'واریز نقدی',
    ];

    /** What each `for` key on the submission form maps to. */
    public const PURPOSES = [
        'topup' => WalletTopup::class,
        'plan' => Subscription::class,
        'line' => LineOrder::class,
        'package' => PackageOrder::class,
        'invoice' => Invoice::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function receiptable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? (string) $this->status;
    }

    public function getTransferTypeLabelAttribute(): string
    {
        return self::TRANSFER_TYPES[$this->transfer_type] ?? (string) $this->transfer_type;
    }

    public function getPurposeLabelAttribute(): string
    {
        return match ($this->receiptable_type) {
            null => 'شارژ کیف پول',
            WalletTopup::class => 'شارژ کیف پول',
            Subscription::class => 'خرید پلن',
            LineOrder::class => 'خرید خط',
            PackageOrder::class => 'خرید بسته پیامکی',
            Invoice::class => 'صورت‌حساب',
            default => 'پرداخت',
        };
    }
}
