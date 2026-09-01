<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class DocArticle extends Model
{
    protected $fillable = [
        'doc_category_id',
        'title',
        'slug',
        'excerpt',
        'body',
        'http_method',
        'endpoint',
        'sort',
        'is_published',
        'seo_title',
        'seo_description',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'sort' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (DocArticle $article) {
            if (blank($article->slug)) {
                $base = Str::slug($article->title);
                $article->slug = $base !== '' ? $base : 'article-'.Str::random(6);
            }

            if ($article->http_method) {
                $article->http_method = strtoupper($article->http_method);
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(DocCategory::class, 'doc_category_id');
    }

    public function codeSamples(): HasMany
    {
        return $this->hasMany(DocCodeSample::class)->orderBy('sort')->orderBy('id');
    }

    public function parameters(): HasMany
    {
        return $this->hasMany(DocParameter::class)->orderBy('sort')->orderBy('id');
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
