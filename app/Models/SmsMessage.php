<?php

namespace App\Models;

use App\Services\Sms\UserSmsGateway;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One single SMS a customer sent from the panel (docs/starter.md §12). The send
 * goes through the customer's own Melipayamak credentials ({@see UserSmsGateway});
 * this row records the outcome — both the send result (`status`) and, once the
 * scheduled `sms:delivery-sync` has polled it, the carrier delivery receipt
 * (`delivery_status`, docs/starter.md §14 "Delivery").
 */
class SmsMessage extends Model
{
    protected $fillable = [
        'user_id',
        'to',
        'from',
        'body',
        'parts',
        'rec_id',
        'status',
        'error',
        'delivery_status',
        'delivery_code',
        'delivery_checked_at',
    ];

    protected $casts = [
        'parts' => 'integer',
        'delivery_checked_at' => 'datetime',
    ];

    public const STATUSES = [
        'queued' => 'در صف',
        'sent' => 'ارسال شد',
        'failed' => 'ناموفق',
    ];

    /** Carrier delivery outcome (docs/starter.md §14). `null` = not polled yet. */
    public const DELIVERY_STATUSES = [
        'pending' => 'در انتظار تحویل',
        'delivered' => 'تحویل شد',
        'undelivered' => 'تحویل نشد',
        'failed' => 'خطای مخابراتی',
        'unknown' => 'نامشخص',
    ];

    /** Once the delivery status is one of these it is settled — stop polling. */
    public const DELIVERY_FINAL = ['delivered', 'undelivered', 'failed'];

    /**
     * Map a raw GetDelivery2 code (or an already-worded status from another
     * driver) to one of {@see DELIVERY_STATUSES}. Codes per
     * https://www.melipayamak.com/api/getdelivery2/. Returns null for an
     * empty/absent report so the message stays in the poll queue.
     */
    public static function mapDeliveryStatus(int|string|null $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        $value = trim((string) $raw);

        if ($value === '' || strcasecmp($value, 'null') === 0) {
            return null;
        }

        if (is_numeric($value)) {
            return match ((int) $value) {
                1 => 'delivered',
                2, 16 => 'undelivered',
                3, 5, 100 => 'failed',
                0, 8 => 'pending',
                default => 'unknown',
            };
        }

        $slug = strtolower($value);

        return match ($slug) {
            'delivered' => 'delivered',
            'undelivered', 'undeliverable' => 'undelivered',
            'failed', 'error' => 'failed',
            'pending', 'sent', 'sending', 'queued' => 'pending',
            default => array_key_exists($slug, self::DELIVERY_STATUSES) ? $slug : 'unknown',
        };
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Sent messages whose carrier receipt is not settled yet — the working set
     * for `sms:delivery-sync`.
     */
    public function scopeAwaitingDelivery(Builder $query): Builder
    {
        return $query
            ->where('status', 'sent')
            ->whereNotNull('rec_id')
            ->where(function (Builder $q) {
                $q->whereNull('delivery_status')
                    ->orWhereNotIn('delivery_status', self::DELIVERY_FINAL);
            });
    }

    public function deliveryIsFinal(): bool
    {
        return in_array($this->delivery_status, self::DELIVERY_FINAL, true);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? (string) $this->status;
    }

    public function getDeliveryStatusLabelAttribute(): ?string
    {
        if ($this->delivery_status === null) {
            return null;
        }

        return self::DELIVERY_STATUSES[$this->delivery_status] ?? $this->delivery_status;
    }
}
