<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A customer phonebook group (docs/starter.md §17). Mirrored to the customer's
 * own Melipayamak panel when they have one — `remote_id` is the Melipayamak
 * GroupID, `sync_status` tracks the mirror ({@see \App\Support\PhonebookSync}).
 */
class ContactGroup extends Model
{
    /** Mirror state against the Melipayamak phonebook. */
    public const SYNC_STATUSES = [
        'local' => 'فقط محلی',
        'synced' => 'همگام با ملی‌پیامک',
        'error' => 'خطای همگام‌سازی',
    ];

    protected $fillable = [
        'user_id',
        'remote_id',
        'name',
        'description',
        'show_to_child',
        'contact_count',
        'sync_status',
        'sync_error',
        'synced_at',
    ];

    protected $casts = [
        'show_to_child' => 'boolean',
        'contact_count' => 'integer',
        'synced_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('name');
    }

    public function getSyncStatusLabelAttribute(): string
    {
        return self::SYNC_STATUSES[$this->sync_status] ?? (string) $this->sync_status;
    }
}
