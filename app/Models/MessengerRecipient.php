<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One recipient of a {@see MessengerCampaign} (docs/starter.md §91) with its own
 * outcome — so a failed number can be seen and the failed portion refunded.
 */
class MessengerRecipient extends Model
{
    protected $fillable = [
        'messenger_campaign_id',
        'to',
        'type',
        'status',
        'provider_ref',
        'error',
    ];

    public const STATUSES = [
        'queued' => 'در صف',
        'sent' => 'ارسال شد',
        'failed' => 'ناموفق',
    ];

    public const TYPES = [
        'mobile' => 'شماره موبایل',
        'chat' => 'شناسه/کاربری',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(MessengerCampaign::class, 'messenger_campaign_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? (string) $this->status;
    }
}
