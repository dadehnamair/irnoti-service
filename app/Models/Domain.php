<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A vanity-link domain (e.g. 11v.ir, 7db.ir, irnoti.com) that BusinessCard codes
 * can be sold under. Each domain owns its own code-price tiers so a future
 * domain can have completely different pricing. Public resolution happens by
 * matching the incoming Host header — see PublicBusinessCardController.
 */
class Domain extends Model
{
    protected $fillable = [
        'host',
        'label',
        'is_active',
        'is_default',
        'code_price_tiers',
        'sort',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'code_price_tiers' => 'array',
        'sort' => 'integer',
    ];

    protected static function booted(): void
    {
        // Only one default domain at a time.
        static::saved(function (Domain $domain) {
            if ($domain->is_default) {
                static::query()->whereKeyNot($domain->getKey())
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }
        });
    }

    public function cards(): HasMany
    {
        return $this->hasMany(BusinessCard::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort')->orderBy('id');
    }

    /**
     * The character class of a code: only digits, only letters, or a mix —
     * matched against each tier's "type" to find the applicable price.
     */
    private function codeType(string $code): string
    {
        $isNumeric = (bool) preg_match('/^[0-9]+$/', $code);
        $isAlpha = (bool) preg_match('/^\p{L}+$/u', $code);

        return match (true) {
            $isNumeric => 'numeric',
            $isAlpha => 'alpha',
            default => 'mixed',
        };
    }

    /**
     * The first configured price tier whose length range and type match the
     * given code, or null when nothing in this domain's tiers covers it.
     *
     * @return array{type: string, min_length: int, max_length: int, price: int, label?: string}|null
     */
    public function tierForCode(string $code): ?array
    {
        $length = mb_strlen($code);
        $type = $this->codeType($code);

        foreach ((array) $this->code_price_tiers as $tier) {
            $tierType = $tier['type'] ?? 'mixed';
            $matchesType = $tierType === 'mixed' || $tierType === $type;

            if ($matchesType
                && $length >= (int) ($tier['min_length'] ?? 0)
                && $length <= (int) ($tier['max_length'] ?? PHP_INT_MAX)) {
                return $tier;
            }
        }

        return null;
    }
}
