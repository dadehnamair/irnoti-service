<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A per-user exception to the access-group grants (docs/starter.md §15):
 * `mode = grant` adds a feature the group lacks, `mode = revoke` removes one it has.
 */
class UserFeatureOverride extends Model
{
    public const MODES = [
        'grant' => 'فعال‌سازی (افزودن)',
        'revoke' => 'محدودسازی (حذف)',
    ];

    protected $fillable = ['user_id', 'feature_id', 'mode'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function feature(): BelongsTo
    {
        return $this->belongsTo(Feature::class);
    }
}
