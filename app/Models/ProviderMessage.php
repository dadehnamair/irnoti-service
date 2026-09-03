<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row of the customer's provider-side message archive (docs/starter.md §14),
 * mirrored locally by {@see App\Jobs\SyncProviderMessagesJob}. `direction` is
 * `inbox` (a message a user sent to one of the account's سرشماره‌ها) or `sent`
 * (a message the account sent). De-duped on `provider_msg_id` per user+direction.
 */
class ProviderMessage extends Model
{
    protected $fillable = [
        'user_id',
        'direction',
        'provider_msg_id',
        'sender',
        'receiver',
        'body',
        'parts',
        'rec_count',
        'rec_success',
        'rec_failed',
        'sent_at',
    ];

    protected $casts = [
        'parts' => 'integer',
        'rec_count' => 'integer',
        'rec_success' => 'integer',
        'rec_failed' => 'integer',
        'sent_at' => 'datetime',
    ];

    public const DIRECTIONS = [
        'inbox' => 'دریافتی',
        'sent' => 'ارسالی',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForBox(Builder $query, int $userId, string $direction): Builder
    {
        return $query->where('user_id', $userId)->where('direction', $direction);
    }
}
