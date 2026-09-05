<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * An admin-defined sales-representation tier ("پنل نمایندگی") shown on the
 * public /representation page. See docs/sales-representation.md.
 */
class RepresentationTier extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'tagline',
        'description',
        'investment_amount',
        'commission_percent',
        'benefits',
        'requirements',
        'is_featured',
        'is_active',
        'sort',
    ];

    protected $casts = [
        'investment_amount' => 'integer',
        'commission_percent' => 'integer',
        'benefits' => 'array',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'sort' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (RepresentationTier $tier) {
            if (blank($tier->slug)) {
                $base = Str::slug($tier->name);
                $tier->slug = $base !== '' ? $base : 'tier-'.Str::random(6);
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function applications(): HasMany
    {
        return $this->hasMany(RepresentationApplication::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort')->orderBy('id');
    }

    public function getBenefitListAttribute(): array
    {
        return array_values(array_filter((array) $this->benefits));
    }

    /** "بدون نیاز به سرمایه" یا "۱۰۰٬۰۰۰٬۰۰۰ تومان". */
    public function getInvestmentLabelAttribute(): string
    {
        return $this->investment_amount
            ? number_format((int) $this->investment_amount).' تومان'
            : 'بدون نیاز به سرمایه';
    }
}
