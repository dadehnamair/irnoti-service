<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;
use Morilog\Jalali\Jalalian;

/**
 * A billing document issued by the admin (docs/starter.md §22 / §51). The
 * customer pays it online, from wallet balance, or by bank receipt; the
 * admin's confirmation (auto for online/wallet) marks it paid. Route key is the
 * unguessable `token`; `number` (INV-1405-0001) is what people quote.
 */
class Invoice extends Model
{
    protected $fillable = [
        'token',
        'number',
        'user_id',
        'title',
        'status',
        'subtotal',
        'discount',
        'tax',
        'total',
        'description',
        'payment_method',
        'issued_at',
        'due_at',
        'paid_at',
    ];

    protected $casts = [
        'subtotal' => 'integer',
        'discount' => 'integer',
        'tax' => 'integer',
        'total' => 'integer',
        'issued_at' => 'datetime',
        'due_at' => 'date',
        'paid_at' => 'datetime',
    ];

    public const STATUSES = [
        'draft' => 'پیش‌نویس',
        'issued' => 'صادر شده',
        'awaiting_payment' => 'در انتظار پرداخت',
        'paid' => 'پرداخت شده',
        'cancelled' => 'لغو شده',
    ];

    protected static function booted(): void
    {
        static::creating(function (Invoice $invoice) {
            if (blank($invoice->token)) {
                $invoice->token = Str::lower(Str::random(24));
            }

            if (blank($invoice->number)) {
                $invoice->number = static::nextNumber();
            }
        });
    }

    public static function nextNumber(): string
    {
        $prefix = (string) Setting::get('invoice_number_prefix', 'INV');
        $year = Jalalian::now()->getYear();
        $seq = static::query()->where('number', 'like', "{$prefix}-{$year}-%")->count() + 1;

        return sprintf('%s-%d-%04d', $prefix, $year, $seq);
    }

    public function getRouteKeyName(): string
    {
        return 'token';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('sort')->orderBy('id');
    }

    public function receipts(): MorphMany
    {
        return $this->morphMany(BankReceipt::class, 'receiptable');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? (string) $this->status;
    }

    /** Recompute subtotal/total from the items; keeps manual discount & tax. */
    public function recalculateTotals(): void
    {
        $subtotal = (int) $this->items()->sum('amount');
        $total = max(0, $subtotal - (int) $this->discount + (int) $this->tax);

        $this->forceFill(['subtotal' => $subtotal, 'total' => $total])->saveQuietly();
    }

    public function isPayable(): bool
    {
        return $this->total > 0
            && in_array($this->status, ['issued', 'awaiting_payment'], true);
    }
}
