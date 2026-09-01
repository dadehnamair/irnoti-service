<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * A request to buy a dedicated line (docs/starter.md §11). Captured from the
 * public /lines page; the admin walks it through the status workflow.
 */
class LineOrder extends Model
{
    protected $fillable = [
        'token',
        'sms_line_id',
        'line_label',
        'price',
        'customer_name',
        'customer_phone',
        'customer_email',
        'company',
        'desired_number',
        'note',
        'status',
        'admin_note',
        'transaction_id',
        'reference_id',
        'payment_driver',
        'paid_at',
    ];

    protected $casts = [
        'price' => 'integer',
        'paid_at' => 'datetime',
    ];

    /** §11 status workflow → Persian label. */
    public const STATUSES = [
        'pending' => 'در انتظار بررسی',
        'awaiting_payment' => 'در انتظار پرداخت',
        'paid' => 'پرداخت شده',
        'processing' => 'در حال انجام',
        'completed' => 'تکمیل شده',
        'rejected' => 'رد شده',
        'cancelled' => 'لغو شده',
    ];

    protected static function booted(): void
    {
        static::creating(function (LineOrder $order) {
            if (blank($order->token)) {
                $order->token = Str::lower(Str::random(24));
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'token';
    }

    public function line()
    {
        return $this->belongsTo(SmsLine::class, 'sms_line_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /** Still owes money and can be sent to the gateway. */
    public function isPayable(): bool
    {
        return $this->price > 0
            && in_array($this->status, ['pending', 'awaiting_payment'], true);
    }
}
