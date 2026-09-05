<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * One card in the public marketing "امکانات" (features) catalogue — feeds the
 * landing page's #features teaser and the dedicated /features showcase page.
 * Distinct from `Feature` (the dashboard sidebar gating catalogue driven by
 * App\Support\FeatureCatalog): this is pure marketing copy, fully admin-managed.
 */
class SiteFeature extends Model
{
    public const CATEGORIES = [
        'sms' => 'ارسال پیامک',
        'lines' => 'خطوط اختصاصی',
        'finance' => 'مالی و کیف پول',
        'marketplace' => 'بازارچه',
        'cards' => 'کارت ویزیت دیجیتال',
        'messenger' => 'پیام‌رسان‌ها',
        'contacts' => 'مخاطبین',
        'developers' => 'توسعه‌دهندگان',
        'security' => 'دسترسی و امنیت',
        'sales' => 'فروش و همکاری',
        'other' => 'سایر',
    ];

    protected $fillable = [
        'icon',
        'title',
        'tagline',
        'description',
        'category',
        'badge',
        'href',
        'is_featured',
        'is_active',
        'sort',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'sort' => 'integer',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort')->orderBy('id');
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? (string) $this->category;
    }
}
