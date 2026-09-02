<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * A dedicated SMS line offered for sale (docs/starter.md §9 / §10).
 * Managed from the Filament admin panel; rendered on the public /lines page.
 */
class SmsLine extends Model
{
    protected $fillable = [
        'prefix',
        'operator',
        'number',
        'digits',
        'line_type',
        'is_rond',
        'price',
        'reseller_price',
        'compare_at_price',
        'description',
        'features',
        'sale_status',
        'requires_inquiry',
        'is_active',
        'sort',
    ];

    protected $casts = [
        'digits' => 'integer',
        'is_rond' => 'boolean',
        'price' => 'integer',
        'reseller_price' => 'integer',
        'compare_at_price' => 'integer',
        'features' => 'array',
        'requires_inquiry' => 'boolean',
        'is_active' => 'boolean',
        'sort' => 'integer',
    ];

    /** Persian labels for the `line_type` column. */
    public const TYPES = [
        'shared' => 'اشتراکی',
        'dedicated' => 'اختصاصی',
        'international' => 'بین‌الملل',
        'service' => 'خدماتی',
    ];

    /** Persian labels for the `sale_status` column. */
    public const SALE_STATUSES = [
        'available' => 'آماده فروش',
        'reserved' => 'رزرو شده',
        'sold' => 'فروخته شده',
    ];

    public function orders()
    {
        return $this->hasMany(LineOrder::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('sale_status', 'available');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort')->orderBy('price')->orderBy('id');
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->line_type] ?? $this->line_type;
    }

    public function getSaleStatusLabelAttribute(): string
    {
        return self::SALE_STATUSES[$this->sale_status] ?? $this->sale_status;
    }

    public function getFeatureListAttribute(): array
    {
        return array_values(array_filter((array) $this->features));
    }

    /** "خطوط 3000" — the group heading + tab label. */
    public function getGroupLabelAttribute(): string
    {
        return 'خطوط '.$this->prefix;
    }

    /** Display number: the explicit one, else "3000XXXXXXXX" from the prefix. */
    public function getDisplayNumberAttribute(): string
    {
        return $this->prefix;
    }

    public function getDisplayNumberXAttribute(): string
    {
        if (filled($this->number)) {
            return $this->number;
        }

        $rest = max($this->digits - mb_strlen($this->prefix), 0);

        return str_repeat('X', $rest);
    }
}
