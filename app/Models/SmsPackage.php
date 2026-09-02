<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * A one-off SMS credit bundle (docs/starter.md §12). Mirrors {@see Plan}: slug is
 * auto-filled, admin-managed, has active/ordered scopes and a slug route key.
 */
class SmsPackage extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'sms_count',
        'price',
        'compare_at_price',
        'badge_label',
        'description',
        'is_featured',
        'is_active',
        'sort',
    ];

    protected $casts = [
        'sms_count' => 'integer',
        'price' => 'integer',
        'compare_at_price' => 'integer',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'sort' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (SmsPackage $package) {
            if (blank($package->slug)) {
                $base = Str::slug($package->name);
                $package->slug = $base !== '' ? $base : 'package-'.Str::random(6);
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort')->orderBy('id');
    }

    public function isFree(): bool
    {
        return (int) $this->price === 0;
    }

    /** Toman per single SMS, for the "هر پیامک X تومان" line. */
    public function getUnitPriceAttribute(): float
    {
        return $this->sms_count > 0 ? round($this->price / $this->sms_count, 2) : 0;
    }
}
