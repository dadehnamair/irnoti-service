<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * «باندل اختصاصی خط» (docs/lines-landing.md) — a dedicated line + SMS credit +
 * validity window sold as one product from a {@see LineGroup} landing page.
 * Purchased through the shared LineOrder flow (see LineController::order()).
 */
class LineBundle extends Model
{
    protected $fillable = [
        'line_group_id',
        'sms_line_id',
        'slug',
        'title',
        'description',
        'sms_credit',
        'validity_days',
        'price',
        'compare_at_price',
        'badge_label',
        'badge_style',
        'features',
        'sort',
        'is_active',
    ];

    protected $casts = [
        'sms_credit' => 'integer',
        'validity_days' => 'integer',
        'price' => 'integer',
        'compare_at_price' => 'integer',
        'features' => 'array',
        'sort' => 'integer',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (LineBundle $bundle) {
            if (blank($bundle->slug)) {
                $base = Str::slug((string) $bundle->title);
                $bundle->slug = $base !== '' ? $base : 'bundle-'.Str::lower(Str::random(6));
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(LineGroup::class, 'line_group_id');
    }

    public function smsLine(): BelongsTo
    {
        return $this->belongsTo(SmsLine::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort')->orderBy('price')->orderBy('id');
    }

    public function getFeatureListAttribute(): array
    {
        return array_values(array_filter((array) $this->features));
    }
}
