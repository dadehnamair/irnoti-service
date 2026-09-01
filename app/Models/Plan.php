<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'badge_label',
        'badge_style',
        'price_monthly',
        'price_yearly',
        'compare_at_monthly',
        'compare_at_yearly',
        'duration_days',
        'sms_count',
        'lines_count',
        'users_count',
        'features',
        'cta_label',
        'cta_style',
        'cta_url',
        'color',
        'is_featured',
        'is_active',
        'sort',
    ];

    protected $casts = [
        'features' => 'array',
        'price_monthly' => 'integer',
        'price_yearly' => 'integer',
        'compare_at_monthly' => 'integer',
        'compare_at_yearly' => 'integer',
        'duration_days' => 'integer',
        'sms_count' => 'integer',
        'lines_count' => 'integer',
        'users_count' => 'integer',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'sort' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (Plan $plan) {
            if (blank($plan->slug)) {
                $base = Str::slug($plan->name);
                $plan->slug = $base !== '' ? $base : 'plan-'.Str::random(6);
            }

            if (blank($plan->price_yearly)) {
                // Sensible default: 10 months' worth for a yearly commitment.
                $plan->price_yearly = $plan->price_monthly * 10;
            }
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort')->orderBy('id');
    }

    public function getFeatureListAttribute(): array
    {
        return array_values(array_filter((array) $this->features));
    }
}
