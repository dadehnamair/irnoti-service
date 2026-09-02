<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A customer phonebook contact (docs/starter.md §17). Mirrored to the customer's
 * own Melipayamak panel — `remote_id` is the Melipayamak ContactID,
 * `sync_status` tracks the mirror ({@see \App\Support\PhonebookSync}).
 */
class Contact extends Model
{
    public const GENDERS = [
        'female' => 'زن',
        'male' => 'مرد',
    ];

    /** Mirror state against the Melipayamak phonebook. */
    public const SYNC_STATUSES = ContactGroup::SYNC_STATUSES;

    protected $fillable = [
        'user_id',
        'remote_id',
        'first_name',
        'last_name',
        'mobile',
        'email',
        'company',
        'nickname',
        'gender',
        'birth_date',
        'description',
        'sync_status',
        'sync_error',
        'synced_at',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'synced_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (Contact $contact) {
            if ($contact->isDirty('mobile') && filled($contact->mobile)) {
                $contact->mobile = normalize_mobile($contact->mobile);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(ContactGroup::class);
    }

    public function getFullNameAttribute(): string
    {
        $full = trim(($this->first_name ?? '').' '.($this->last_name ?? ''));

        return $full !== '' ? $full : (string) $this->mobile;
    }

    public function getGenderLabelAttribute(): ?string
    {
        return $this->gender ? (self::GENDERS[$this->gender] ?? $this->gender) : null;
    }

    public function getSyncStatusLabelAttribute(): string
    {
        return self::SYNC_STATUSES[$this->sync_status] ?? (string) $this->sync_status;
    }
}
