<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

/**
 * A lead submitted from the public /representation form — reviewed manually
 * by the admin (no self-service payment/activation). See
 * docs/sales-representation.md.
 */
class RepresentationApplication extends Model
{
    protected $fillable = [
        'representation_tier_id',
        'full_name',
        'mobile',
        'email',
        'city',
        'company_name',
        'message',
        'status',
        'admin_note',
    ];

    /** Persian labels for the `status` column. */
    public const STATUSES = [
        'pending' => 'در انتظار بررسی',
        'contacted' => 'تماس گرفته‌شده',
        'approved' => 'تأیید شده',
        'rejected' => 'رد شده',
    ];

    public function tier(): BelongsTo
    {
        return $this->belongsTo(RepresentationTier::class, 'representation_tier_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
