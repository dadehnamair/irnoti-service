<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One single SMS a customer sent from the panel (docs/starter.md §12). The send
 * goes through the customer's own Melipayamak credentials ({@see \App\Services\Sms\UserSmsGateway});
 * this row records the outcome.
 */
class SmsMessage extends Model
{
    protected $fillable = [
        'user_id',
        'to',
        'body',
        'parts',
        'rec_id',
        'status',
        'error',
    ];

    protected $casts = [
        'parts' => 'integer',
    ];

    public const STATUSES = [
        'queued' => 'در صف',
        'sent' => 'ارسال شد',
        'failed' => 'ناموفق',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? (string) $this->status;
    }
}
