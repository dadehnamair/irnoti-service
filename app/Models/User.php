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
        'awaiting_approval' => 'در انتظار تأیید',
        'active' => 'فعال',
        'suspended' => 'معلق',
        'blocked' => 'مسدود',
    ];

    /** Document review stage — separate from the account status (docs/starter.md §26). */
    public const DOCUMENT_STATUSES = [
        'pending' => 'در انتظار بررسی',
        'approved' => 'تأیید شده',
        'rejected' => 'رد شده',
    ];

    /** Identity fields the customer may no longer edit once the account is approved. */
    public const LOCKED_IDENTITY_FIELDS = ['first_name', 'last_name', 'national_code', 'birth_cert_number'];

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
        'approved_at',
        'documents_status',
        'documents_reviewed_at',
        'documents_reject_reason',
        'sms_username',
        'sms_password',
        'sms_sender',
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
            'approved_at' => 'datetime',
            'documents_reviewed_at' => 'datetime',
            'sms_password' => 'encrypted',
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

    public function smsMessages(): HasMany
    {
        return $this->hasMany(SmsMessage::class);
    }

    public function lineOrders(): HasMany
    {
        return $this->hasMany(LineOrder::class);
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

    /** Admin has reviewed and approved the account — panel features unlock (docs/starter.md §39). */
    public function isApproved(): bool
    {
        return $this->status === 'active' && $this->approved_at !== null;
    }

    /** Identity fields become read-only for the customer once the account is approved. */
    public function identityLocked(): bool
    {
        return $this->approved_at !== null;
    }

    /** Documents can't be re-uploaded by the customer once approved. */
    public function documentsLocked(): bool
    {
        return $this->documents_status === 'approved';
    }

    /** The admin has wired the customer's own Melipayamak panel credentials. */
    public function hasSmsPanel(): bool
    {
        return filled($this->sms_username) && filled($this->sms_password);
    }

    public function getDocumentsStatusLabelAttribute(): string
    {
        return self::DOCUMENT_STATUSES[$this->documents_status] ?? (string) $this->documents_status;
    }

    /**
     * Move the account between "pending" and "awaiting_approval" as the customer
     * completes the prerequisites (docs/starter.md §39). Never touches an account
     * that is already active/suspended/blocked — those are admin-owned states.
     */
    public function refreshApprovalState(): void
    {
        if (! in_array($this->status, ['pending', 'awaiting_approval'], true)) {
            return;
        }

        $ready = $this->isProfileComplete() && $this->hasActivePlan();
        $target = $ready ? 'awaiting_approval' : 'pending';

        if ($this->status !== $target) {
            $this->forceFill(['status' => $target])->save();
        }
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
