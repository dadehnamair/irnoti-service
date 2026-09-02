<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Services\Sms\UserSmsGateway;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
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

    /** Natural person vs. registered company (docs/starter.md §26). */
    public const ACCOUNT_TYPES = [
        'individual' => 'شخص حقیقی',
        'legal' => 'شخص حقوقی',
    ];

    /**
     * Identity fields the customer may no longer edit once the account is approved.
     * The company_* entries only apply to a legal account (docs/starter.md §26) —
     * the plain optional `company` freetext on an individual account stays editable.
     */
    public const LOCKED_IDENTITY_FIELDS = [
        'first_name', 'last_name', 'national_code', 'birth_cert_number',
        'account_type', 'company_type', 'company_national_id',
        'company_registration_number', 'company_registered_at', 'company_economic_code',
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
        'account_type',
        'first_name',
        'last_name',
        'company',
        'company_type',
        'company_national_id',
        'company_registration_number',
        'company_registered_at',
        'company_economic_code',
        'company_phone',
        'company_postal_code',
        'company_address',
        'rep_role',
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
        'company_registration_doc',
        'company_changes_doc',
        'company_extra_docs',
        'plan_id',
        'user_group_id',
        'plan_expires_at',
        'profile_completed_at',
        'approved_at',
        'documents_status',
        'documents_reviewed_at',
        'documents_reject_reason',
        'sms_username',
        'sms_password',
        'sms_sender',
        'sms_numbers',
        'sms_numbers_synced_at',
        'sms_credit',
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
            'company_extra_docs' => 'array',
            'sms_numbers' => 'array',
            'sms_numbers_synced_at' => 'datetime',
            'sms_credit' => 'integer',
            // NOT hashed / NOT encrypted — Melipayamak's SendSMS API needs the raw
            // panel password, and the admin sets this value straight in the DB
            // (docs/starter.md §12, https://www.melipayamak.com/api/sendsimplesms2/).
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

    /** The customer's access group — the base set of panel features (docs/starter.md §15). */
    public function userGroup(): BelongsTo
    {
        return $this->belongsTo(UserGroup::class);
    }

    /** Per-user grant/revoke exceptions on top of the group (docs/starter.md §15). */
    public function featureOverrides(): HasMany
    {
        return $this->hasMany(UserFeatureOverride::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function smsMessages(): HasMany
    {
        return $this->hasMany(SmsMessage::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    public function contactGroups(): HasMany
    {
        return $this->hasMany(ContactGroup::class);
    }

    public function lineOrders(): HasMany
    {
        return $this->hasMany(LineOrder::class);
    }

    public function walletRelation(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    /** The customer's wallet, created on first access (docs/starter.md §23). */
    public function wallet(): Wallet
    {
        return $this->walletRelation()->firstOrCreate([]);
    }

    public function walletBalance(): int
    {
        return (int) ($this->walletRelation?->balance ?? $this->walletRelation()->value('balance') ?? 0);
    }

    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class)->latest('id');
    }

    public function walletTopups(): HasMany
    {
        return $this->hasMany(WalletTopup::class);
    }

    public function packageOrders(): HasMany
    {
        return $this->hasMany(PackageOrder::class);
    }

    public function bankReceipts(): HasMany
    {
        return $this->hasMany(BankReceipt::class)->latest('id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class)->latest('id');
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

    /**
     * The customer's live Melipayamak panel credit (docs/starter.md §12),
     * cached for 60s under "sms_credit:{id}". Returns
     * ['sms' => ?int, 'rial' => ?int, 'error' => ?string] and never throws —
     * a failed read comes back as `error`, nulls elsewhere. Shared by the SMS
     * panel page and the account sidebar card. `rial` is a Rial amount; render
     * it through rial_to_toman() — the UI only ever shows Toman.
     *
     * @return array{sms: ?int, rial: ?int, error: ?string}
     */
    public function smsPanelCredit(): array
    {
        $empty = ['sms' => null, 'rial' => null, 'error' => null];

        if (! $this->hasSmsPanel()) {
            return $empty;
        }

        $cached = Cache::get("sms_credit:{$this->id}");

        if (is_array($cached)) {
            return ['sms' => $cached['sms'] ?? null, 'rial' => $cached['rial'] ?? null, 'error' => null];
        }

        try {
            $gateway = UserSmsGateway::for($this);
            $sms = $gateway->credit();
            $rial = $gateway->creditRial();

            // Only cache a real read, never a failure.
            Cache::put("sms_credit:{$this->id}", ['sms' => $sms, 'rial' => $rial], now()->addSeconds(60));

            return ['sms' => $sms, 'rial' => $rial, 'error' => null];
        } catch (\Throwable $e) {
            Log::warning('SMS credit read failed', ['user' => $this->id, 'error' => $e->getMessage()]);

            return ['sms' => null, 'rial' => null, 'error' => $e->getMessage()];
        }
    }

    /**
     * The sender lines (سرشماره) this customer may send from — the list cached
     * from Melipayamak's GetUserNumbers, falling back to the single configured
     * default when the list hasn't been synced yet (docs/starter.md §12).
     *
     * @return array<int, string>
     */
    public function availableSmsNumbers(): array
    {
        $list = array_values(array_filter((array) ($this->sms_numbers ?? [])));

        return $list ?: array_values(array_filter([$this->sms_sender]));
    }

    /** The cached sender-number list is missing or older than a day. */
    public function smsNumbersAreStale(): bool
    {
        return $this->sms_numbers_synced_at === null
            || $this->sms_numbers_synced_at->lt(now()->subDay());
    }

    public function getDocumentsStatusLabelAttribute(): string
    {
        return self::DOCUMENT_STATUSES[$this->documents_status] ?? (string) $this->documents_status;
    }

    public function getAccountTypeLabelAttribute(): string
    {
        return self::ACCOUNT_TYPES[$this->account_type] ?? self::ACCOUNT_TYPES['individual'];
    }

    /** Account belongs to a registered company (docs/starter.md §26). */
    public function isLegal(): bool
    {
        return $this->account_type === 'legal';
    }

    /**
     * The panel-feature keys this account is granted (docs/starter.md §15):
     * the access group's features, then per-user overrides applied on top
     * (grant adds, revoke removes). Independent of the global «بزودی» toggle —
     * use canUseFeature() when you also need the feature switched on.
     *
     * @return array<int, string>
     */
    public function grantedFeatureKeys(): array
    {
        $keys = collect(
            $this->user_group_id
                ? UserGroup::query()->whereKey($this->user_group_id)
                    ->first()?->features()->pluck('features.key')->all() ?? []
                : []
        );

        foreach ($this->featureOverrides()->with('feature:id,key')->get() as $override) {
            $key = $override->feature?->key;

            if ($key === null) {
                continue;
            }

            $keys = $override->mode === 'revoke'
                ? $keys->reject(fn ($k) => $k === $key)
                : $keys->push($key);
        }

        return $keys->unique()->values()->all();
    }

    /**
     * A feature is usable when it is switched on globally AND either a built-in
     * page (`is_system`) or granted to the account via the group / overrides.
     */
    public function canUseFeature(string $key): bool
    {
        $feature = Feature::query()->where('key', $key)->first();

        if ($feature === null || ! $feature->is_active) {
            return false;
        }

        return $feature->is_system
            || in_array($key, $this->grantedFeatureKeys(), true);
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
        // A legal account is identified by its company name; the natural-person
        // fields describe the signing representative (docs/starter.md §26).
        if ($this->isLegal() && filled($this->company)) {
            return $this->company;
        }

        $full = trim(($this->first_name ?? '').' '.($this->last_name ?? ''));

        return $full !== '' ? $full : ($this->name ?: ($this->mobile ?? ''));
    }

    /** The natural person on the account — the representative for a legal account. */
    public function getContactNameAttribute(): string
    {
        $full = trim(($this->first_name ?? '').' '.($this->last_name ?? ''));

        return $full !== '' ? $full : ($this->mobile ?? '');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? (string) $this->status;
    }

    /** Keep `name` (used by Filament / notifications) in sync with the profile fields. */
    protected static function booted(): void
    {
        // New customers land in the default access group (docs/starter.md §15);
        // rescue() keeps registration working on a fresh DB / mid-migration.
        static::creating(function (User $user) {
            if ($user->user_group_id === null && ! $user->is_admin) {
                $user->user_group_id = rescue(fn () => UserGroup::defaultId(), null, false);
            }
        });

        static::saving(function (User $user) {
            if ($user->isDirty(['first_name', 'last_name', 'company', 'account_type']) || blank($user->name)) {
                // Legal account → show the company; otherwise the person's full name.
                $full = $user->account_type === 'legal' && filled($user->company)
                    ? trim($user->company)
                    : trim(($user->first_name ?? '').' '.($user->last_name ?? ''));
                $user->name = $full !== '' ? $full : ($user->name ?: 'کاربر '.Str::of($user->mobile ?? '')->substr(-4));
            }
        });
    }
}
