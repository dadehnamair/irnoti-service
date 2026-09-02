<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One item of the customer dashboard mega-menu (docs/starter.md §15). Rows are
 * born from App\Support\FeatureCatalog via FeaturesSeeder; the admin only toggles
 * `is_active` («بزودی» → live) and tweaks label / route / sort.
 */
class Feature extends Model
{
    protected $fillable = [
        'key', 'group_key', 'group_label', 'label',
        'icon', 'route', 'url', 'description', 'sort', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort' => 'integer',
        ];
    }

    public function userGroups(): BelongsToMany
    {
        return $this->belongsToMany(UserGroup::class);
    }

    public function overrides(): HasMany
    {
        return $this->hasMany(UserFeatureOverride::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort')->orderBy('id');
    }
}
