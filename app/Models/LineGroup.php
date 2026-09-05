<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * A per-prefix landing page for dedicated SMS lines (docs/lines-landing.md).
 * Groups all the {@see SmsLine} rows that share a `prefix`, and carries the
 * admin-edited marketing copy / SEO / FAQ the public /lines/{group} page shows
 * alongside that prefix's purchase variants and {@see LineBundle}s.
 */
class LineGroup extends Model
{
    protected $fillable = [
        'slug',
        'prefix',
        'title',
        'tagline',
        'body',
        'features',
        'use_cases',
        'faqs',
        'seo_title',
        'seo_description',
        'og_image',
        'sort',
        'is_active',
    ];

    protected $casts = [
        'features' => 'array',
        'use_cases' => 'array',
        'faqs' => 'array',
        'sort' => 'integer',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (LineGroup $group) {
            if (blank($group->slug)) {
                $base = Str::slug((string) ($group->prefix ?: $group->title));
                $group->slug = $base !== '' ? $base : 'line-'.Str::lower(Str::random(6));
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SmsLine::class);
    }

    public function bundles(): HasMany
    {
        return $this->hasMany(LineBundle::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort')->orderBy('id');
    }

    public function getRenderedBodyAttribute(): string
    {
        return $this->body ? Str::markdown($this->body, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]) : '';
    }

    public function getFeatureListAttribute(): array
    {
        return array_values(array_filter((array) $this->features));
    }

    public function getUseCaseListAttribute(): array
    {
        return array_values(array_filter((array) $this->use_cases));
    }

    /** @return list<array{q: string, a: string}> — only rows with both a question and an answer. */
    public function getFaqListAttribute(): array
    {
        return array_values(array_filter(
            (array) $this->faqs,
            fn ($row) => is_array($row) && filled($row['q'] ?? null) && filled($row['a'] ?? null),
        ));
    }
}
