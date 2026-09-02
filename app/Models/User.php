<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /** Account status workflow (docs/starter.md §39). */
    public const STATUSES = [
        'pending' => 'در انتظار تکمیل',
        'active' => 'فعال',
        'suspended' => 'معلق',
        'blocked' => 'مسدود',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
        'mobile',
        'mobile_verified_at',
        'status',
        'last_login_at',
        'first_name',
        'last_name',
        'company',
        'phone',
        'country',
        'province',
        'city',
        'address',
        'postal_code',
        'national_code',
        'birth_cert_number',
        'description',
        'national_card_image',
        'national_card_back_image',
        'identity_doc_image',
        'plan_id',
        'plan_expires_at',
        'profile_completed_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'mobile_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'plan_expires_at' => 'datetime',
            'profile_completed_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_admin === true;
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function isProfileComplete(): bool
    {
        return $this->profile_completed_at !== null;
    }

    public function hasActivePlan(): bool
    {
        return $this->plan_id !== null
            && ($this->plan_expires_at === null || $this->plan_expires_at->isFuture());
    }

    public function getFullNameAttribute(): string
    {
        $full = trim(($this->first_name ?? '').' '.($this->last_name ?? ''));

        return $full !== '' ? $full : ($this->name ?: ($this->mobile ?? ''));
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? (string) $this->status;
    }

    /** Keep `name` (used by Filament / notifications) in sync with the profile fields. */
    protected static function booted(): void
    {
        static::saving(function (User $user) {
            if ($user->isDirty(['first_name', 'last_name']) || blank($user->name)) {
                $full = trim(($user->first_name ?? '').' '.($user->last_name ?? ''));
                $user->name = $full !== '' ? $full : ($user->name ?: 'کاربر '.Str::of($user->mobile ?? '')->substr(-4));
            }
        });
    }
}
