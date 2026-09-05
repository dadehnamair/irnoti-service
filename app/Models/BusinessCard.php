<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * A self-service digital business card (standard flat-price tier, or a "vip"
 * tier with a custom vanity code priced per the chosen Domain's tiers).
 * Publicly served at {domain}/{code} by PublicBusinessCardController; created
 * and managed by the owner from /dashboard/cards.
 */
class BusinessCard extends Model
{
    protected $fillable = [
        'token',
        'user_id',
        'domain_id',
        'tier',
        'code',
        'title',
        'position',
        'company',
        'bio',
        'avatar_path',
        'cover_path',
        'phone',
        'mobile',
        'whatsapp',
        'telegram',
        'instagram',
        'website',
        'email',
        'address',
        'socials',
        'products',
        'theme_color',
        'status',
        'price',
        'admin_note',
        'views_count',
        'transaction_id',
        'reference_id',
        'payment_driver',
        'paid_at',
    ];

    protected $casts = [
        'socials' => 'array',
        'products' => 'array',
        'price' => 'integer',
        'views_count' => 'integer',
        'paid_at' => 'datetime',
    ];

    public const TIERS = [
        'standard' => 'استاندارد',
        'vip' => 'اختصاصی (کد دلخواه)',
    ];

    public const STATUSES = [
        'draft' => 'پیش‌نویس',
        'awaiting_payment' => 'در انتظار پرداخت',
        'active' => 'فعال',
        'disabled' => 'غیرفعال',
    ];

    protected static function booted(): void
    {
        static::creating(function (BusinessCard $card) {
            if (blank($card->token)) {
                $card->token = Str::lower(Str::random(24));
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function getTierLabelAttribute(): string
    {
        return self::TIERS[$this->tier] ?? $this->tier;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /** Still owes money and can be sent to the gateway or paid from the wallet. */
    public function isPayable(): bool
    {
        return $this->price > 0 && $this->status === 'awaiting_payment';
    }

    /** Full public URL, e.g. https://11v.ir/ali. */
    public function getPublicUrlAttribute(): string
    {
        return 'https://'.$this->domain->host.'/'.$this->code;
    }
}
