<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * One installable add-on in «بازارچه» (docs/starter.md §15). Either an
 * external integration (ایرپلاس) or an internal capability (کارت ویزیت، منشی).
 * Structure and pricing are admin-managed from Filament; behaviour comes from the
 * `handler` class (see config/marketplace.php). Slug auto-fills like {@see Plan}.
 */
class MarketplaceApp extends Model
{
    public const CATEGORIES = [
        'integration' => 'اتصال به سرویس‌ها',
        'messaging' => 'پیام‌رسانی',
        'card' => 'کارت ویزیت و معرفی',
        'tool' => 'ابزار',
        'other' => 'سایر',
    ];

    public const BILLING_TYPES = [
        'free' => 'رایگان',
        'one_time' => 'خرید یک‌باره',
        'subscription' => 'اشتراک دوره‌ای',
    ];

    public const BILLING_PERIODS = [
        'monthly' => 'ماهانه',
        'yearly' => 'سالانه',
    ];

    protected $fillable = [
        'slug',
        'name',
        'vendor',
        'category',
        'tagline',
        'description',
        'icon',
        'accent_color',
        'handler',
        'billing_type',
        'price',
        'billing_period',
        'trial_days',
        'config_schema',
        'capabilities',
        'is_active',
        'is_featured',
        'sort',
        'docs_url',
    ];

    protected $casts = [
        'config_schema' => 'array',
        'capabilities' => 'array',
        'price' => 'integer',
        'trial_days' => 'integer',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'sort' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (MarketplaceApp $app) {
            if (blank($app->slug)) {
                $base = Str::slug($app->name);
                $app->slug = $base !== '' ? $base : 'app-' . Str::random(6);
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function installations(): HasMany
    {
        return $this->hasMany(MarketplaceInstallation::class);
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
        return $this->billing_type === 'free' || (int) $this->price === 0;
    }

    public function isSubscription(): bool
    {
        return $this->billing_type === 'subscription';
    }

    /** The list of connection fields to render on the install form (never null). */
    public function configFields(): array
    {
        return array_values(array_filter((array) $this->config_schema, 'is_array'));
    }

    /** @return array<int, string> */
    public function capabilityKeys(): array
    {
        return array_values(array_filter((array) $this->capabilities, 'is_string'));
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? (string) $this->category;
    }

    public function getBillingTypeLabelAttribute(): string
    {
        return self::BILLING_TYPES[$this->billing_type] ?? (string) $this->billing_type;
    }

    public function getBillingPeriodLabelAttribute(): ?string
    {
        return $this->billing_period
            ? (self::BILLING_PERIODS[$this->billing_period] ?? $this->billing_period)
            : null;
    }

    /** "رایگان" / "۱۲۳٬۴۵۶ تومان" / "۱۲۳٬۴۵۶ تومان / ماهانه". */
    public function getPriceLabelAttribute(): string
    {
        if ($this->isFree()) {
            return 'رایگان';
        }

        $label = number_format((int) $this->price) . ' تومان';

        return $this->isSubscription() && $this->billing_period_label
            ? $label . ' / ' . $this->billing_period_label
            : $label;
    }
}
