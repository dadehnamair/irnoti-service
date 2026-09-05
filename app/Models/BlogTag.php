<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class BlogTag extends Model
{
    protected $fillable = ['name', 'slug', 'meta_title', 'meta_description', 'og_image'];

    protected static function booted(): void
    {
        static::saving(function (BlogTag $tag) {
            if (blank($tag->slug)) {
                $base = Str::slug($tag->name);
                $tag->slug = $base !== '' ? $base : 'tag-'.Str::random(6);
            }
        });
    }

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(BlogPost::class, 'blog_post_tag');
    }

    public function getMetaTitleValueAttribute(): string
    {
        return $this->meta_title ?: 'برچسب: '.$this->name;
    }

    public function getMetaDescriptionValueAttribute(): ?string
    {
        if (blank($this->meta_description)) {
            return null;
        }

        return Str::of($this->meta_description)->squish()->limit(160)->value();
    }

    public function getOgImageUrlAttribute(): ?string
    {
        if (blank($this->og_image)) {
            return null;
        }

        if (Str::startsWith($this->og_image, ['http://', 'https://', '/'])) {
            return $this->og_image;
        }

        return asset('storage/'.ltrim($this->og_image, '/'));
    }
}
