<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * A named bundle of panel features assigned to customers (docs/starter.md §15).
 * Per-user tweaks live in UserFeatureOverride.
 */
class UserGroup extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'is_default', 'sort'];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'sort' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (UserGroup $group) {
            if (blank($group->slug)) {
                $group->slug = Str::slug($group->name) ?: 'group-'.Str::random(6);
            }
        });

        // Only one default group at a time.
        static::saved(function (UserGroup $group) {
            if ($group->is_default) {
                static::query()->whereKeyNot($group->getKey())
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }
        });
    }

    public function features(): BelongsToMany
    {
        return $this->belongsToMany(Feature::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public static function defaultId(): ?int
    {
        return static::query()->where('is_default', true)->value('id');
    }
}
