<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * A generic static content page (About / Cooperation) — one row per slug,
 * edited from the Filament admin panel and rendered by PageController@show.
 */
class Page extends Model
{
    protected $fillable = [
        'slug',
        'title',
        'excerpt',
        'body',
        'seo_title',
        'seo_description',
        'sort',
        'is_published',
    ];

    protected $casts = [
        'sort' => 'integer',
        'is_published' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (Page $page) {
            if (blank($page->slug)) {
                $base = Str::slug($page->title);
                $page->slug = $base !== '' ? $base : 'page-'.Str::random(6);
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function getRenderedBodyAttribute(): string
    {
        return $this->body ? Str::markdown($this->body, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]) : '';
    }
}
