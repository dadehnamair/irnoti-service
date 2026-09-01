<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class BlogPost extends Model
{
    protected $fillable = [
        'blog_category_id',
        'author_id',
        'title',
        'slug',
        'excerpt',
        'body',
        'cover_image',
        'is_published',
        'published_at',
        'meta_title',
        'meta_description',
        'og_image',
        'canonical_url',
        'noindex',
        'views',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'noindex' => 'boolean',
        'published_at' => 'datetime',
        'views' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (BlogPost $post) {
            if (blank($post->slug)) {
                $base = Str::slug($post->title);
                $post->slug = $base !== '' ? $base : 'post-'.Str::random(6);
            }

            if ($post->is_published && blank($post->published_at)) {
                $post->published_at = now();
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class, 'blog_category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(BlogTag::class, 'blog_post_tag');
    }

    /**
     * Published posts whose publish date has passed, newest first.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('is_published', true)
            ->where(function (Builder $q) {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            })
            ->orderByDesc('published_at')
            ->orderByDesc('id');
    }

    public function getRenderedBodyAttribute(): string
    {
        return $this->body ? Str::markdown($this->body, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]) : '';
    }

    public function getReadingMinutesAttribute(): int
    {
        $text = trim(strip_tags((string) $this->rendered_body));

        if ($text === '') {
            return 1;
        }

        $words = count(preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: []);

        // ~150 wpm accounts for Persian compound words joined by ZWNJ.
        return max(1, (int) round($words / 150));
    }

    public function getMetaTitleValueAttribute(): string
    {
        return $this->meta_title ?: $this->title;
    }

    public function getMetaDescriptionValueAttribute(): string
    {
        return Str::of($this->meta_description ?: $this->excerpt ?: strip_tags((string) $this->rendered_body))
            ->squish()
            ->limit(160)
            ->value();
    }

    public function getCoverUrlAttribute(): ?string
    {
        return $this->imageUrl($this->cover_image);
    }

    public function getOgImageUrlAttribute(): ?string
    {
        return $this->imageUrl($this->og_image) ?: $this->cover_url;
    }

    public function getPublishedDateAttribute(): ?Carbon
    {
        return $this->published_at ?? $this->created_at;
    }

    protected function imageUrl(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        if (Str::startsWith($value, ['http://', 'https://', '/'])) {
            return $value;
        }

        return asset('storage/'.ltrim($value, '/'));
    }
}
