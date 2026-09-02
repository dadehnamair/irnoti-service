<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * A company bank account shown to customers on the bank-receipt screen
 * (docs/starter.md §22). Managed from the Filament admin panel.
 */
class BankAccount extends Model
{
    protected $fillable = [
        'bank_name',
        'owner_name',
        'card_number',
        'sheba',
        'account_number',
        'note',
        'sort',
        'is_active',
    ];

    protected $casts = [
        'sort' => 'integer',
        'is_active' => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort')->orderBy('id');
    }

    public function getShebaDisplayAttribute(): ?string
    {
        return $this->sheba ? 'IR'.ltrim($this->sheba, 'Ir ') : null;
    }

    public function getCardDisplayAttribute(): ?string
    {
        if (! $this->card_number) {
            return null;
        }

        return trim(chunk_split(preg_replace('/\D/', '', $this->card_number), 4, ' '));
    }

    public function getLabelAttribute(): string
    {
        return trim($this->bank_name.' — '.$this->owner_name);
    }
}
