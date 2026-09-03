<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One bulk send to a messenger network — بله / ایتا / واتساپ (docs/starter.md
 * §91). Mirrors {@see SmsMessage} for the "queued -> sent/failed" shape, plus a
 * group-send summary (recipient counts) and the wallet money trail (`cost` /
 * `refunded`). Recipients get one {@see MessengerRecipient} row each.
 */
class MessengerCampaign extends Model
{
    protected $fillable = [
        'user_id',
        'channel',
        'body',
        'recipients_count',
        'success_count',
        'failed_count',
        'status',
        'batch_id',
        'cost',
        'refunded',
        'error',
        'scheduled_at',
        'sent_at',
    ];

    protected $casts = [
        'recipients_count' => 'integer',
        'success_count' => 'integer',
        'failed_count' => 'integer',
        'cost' => 'integer',
        'refunded' => 'integer',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public const STATUSES = [
        'queued' => 'در صف',
        'sending' => 'در حال ارسال',
        'sent' => 'ارسال شد',
        'partial' => 'ارسال ناقص',
        'failed' => 'ناموفق',
    ];

    /** A campaign in one of these states has been processed — the job is a no-op. */
    public const FINAL_STATUSES = ['sent', 'partial', 'failed'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(MessengerRecipient::class);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? (string) $this->status;
    }

    /** Brand-neutral channel name (cascade-resolved), e.g. «بله». */
    public function getChannelLabelAttribute(): string
    {
        return (string) config("messenger.channels.{$this->channel}.label", $this->channel);
    }
}
