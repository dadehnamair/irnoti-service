<?php

namespace App\Models;

use App\Support\PhonebookSync;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A customer phonebook group (docs/starter.md §17). Mirrored to the customer's
 * own SMS panel when they have one — `remote_id` is the remote GroupID,
 * `sync_status` tracks the mirror ({@see PhonebookSync}).
 */
class ContactGroup extends Model
{
    /** Mirror state against the remote phonebook. */
    public const SYNC_STATUSES = [
        'local' => 'فقط محلی',
        'synced' => 'همگام با سامانه',
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
        'contacts_synced_at',
    ];

    protected $casts = [
        'show_to_child' => 'boolean',
        'contact_count' => 'integer',
        'synced_at' => 'datetime',
        'contacts_synced_at' => 'datetime',
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
