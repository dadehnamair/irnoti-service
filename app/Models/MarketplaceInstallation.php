<?php

namespace App\Models;

use App\Marketplace\AppRegistry;
use App\Marketplace\Contracts\AppHandler;
use App\Support\PayableSettlement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

/**
 * A customer's installation of a {@see MarketplaceApp} (docs/starter.md §15).
 * Mirrors {@see Subscription}: `token` route key, snapshot columns, status
 * workflow, gateway payment columns. Settling it (via {@see PayableSettlement})
 * runs the handler's onActivate() and lights up any capability features.
 */
class MarketplaceInstallation extends Model
{
    public const STATUSES = [
        'pending' => 'در انتظار بررسی',
        'awaiting_payment' => 'در انتظار پرداخت',
        'active' => 'فعال',
        'expired' => 'منقضی شده',
        'suspended' => 'معلق',
        'cancelled' => 'حذف شده',
    ];

    protected $fillable = [
        'token',
        'user_id',
        'marketplace_app_id',
        'status',
        'config',
        'settings',
        'price',
        'billing_type',
        'billing_period',
        'payment_driver',
        'transaction_id',
        'reference_id',
        'paid_at',
        'installed_at',
        'activated_at',
        'expires_at',
        'last_used_at',
        'last_synced_at',
        'admin_note',
    ];

    protected $casts = [
        'config' => 'encrypted:array',
        'settings' => 'array',
        'price' => 'integer',
        'paid_at' => 'datetime',
        'installed_at' => 'datetime',
        'activated_at' => 'datetime',
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
        'last_synced_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (MarketplaceInstallation $installation) {
            if (blank($installation->token)) {
                $installation->token = Str::lower(Str::random(24));
            }

            $installation->installed_at ??= now();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'token';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function app(): BelongsTo
    {
        return $this->belongsTo(MarketplaceApp::class, 'marketplace_app_id');
    }

    public function contactGroups(): HasMany
    {
        return $this->hasMany(ContactGroup::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    public function receipts(): MorphMany
    {
        return $this->morphMany(BankReceipt::class, 'receiptable');
    }

    public function handler(): AppHandler
    {
        return app(AppRegistry::class)->for($this);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? (string) $this->status;
    }

    /** Still owes money and can be sent to the gateway (mirrors {@see PackageOrder}). */
    public function isPayable(): bool
    {
        return (int) $this->price > 0
            && in_array($this->status, ['pending', 'awaiting_payment'], true);
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && ! $this->isExpired();
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /** A given config value, decrypted (or the fallback). */
    public function configValue(string $key, mixed $default = null): mixed
    {
        return data_get($this->config, $key, $default);
    }

    /** A given handler-state value from `settings`. */
    public function settingValue(string $key, mixed $default = null): mixed
    {
        return data_get($this->settings, $key, $default);
    }

    /** Merge one key into `settings` and persist. */
    public function putSetting(string $key, mixed $value): void
    {
        $this->forceFill(['settings' => array_merge((array) $this->settings, [$key => $value])])->save();
    }
}
