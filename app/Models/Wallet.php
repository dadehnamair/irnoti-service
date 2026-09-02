<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * A customer's Toman wallet (docs/starter.md §23 / §49). Balance is an integer —
 * never a float. All movement goes through {@see credit()} / {@see debit()},
 * which lock the row, write an immutable {@see WalletTransaction}, and are safe
 * to replay when given an `$idempotencyKey` (docs/starter.md §25).
 */
class Wallet extends Model
{
    protected $fillable = [
        'user_id',
        'balance',
    ];

    protected $casts = [
        'balance' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class)->latest('id');
    }

    public function hasSufficient(int $amount): bool
    {
        return $this->balance >= $amount;
    }

    /** Add money to the wallet. Returns the ledger row (existing one on replay). */
    public function credit(int $amount, string $type, ?Model $reference = null, string $description = '', ?string $idempotencyKey = null, array $meta = []): WalletTransaction
    {
        return $this->move('credit', $amount, $type, $reference, $description, $idempotencyKey, $meta);
    }

    /** Take money from the wallet. Throws when the balance is not enough. */
    public function debit(int $amount, string $type, ?Model $reference = null, string $description = '', ?string $idempotencyKey = null, array $meta = []): WalletTransaction
    {
        return $this->move('debit', $amount, $type, $reference, $description, $idempotencyKey, $meta);
    }

    private function move(string $direction, int $amount, string $type, ?Model $reference, string $description, ?string $idempotencyKey, array $meta): WalletTransaction
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Wallet movement amount must be positive.');
        }

        return DB::transaction(function () use ($direction, $amount, $type, $reference, $description, $idempotencyKey, $meta) {
            if ($idempotencyKey) {
                $existing = WalletTransaction::where('idempotency_key', $idempotencyKey)->first();

                if ($existing) {
                    return $existing;
                }
            }

            /** @var self $wallet */
            $wallet = self::query()->whereKey($this->getKey())->lockForUpdate()->first();

            $before = (int) $wallet->balance;
            $after = $direction === 'debit' ? $before - $amount : $before + $amount;

            if ($after < 0) {
                throw new InvalidArgumentException('موجودی کیف پول کافی نیست.');
            }

            $wallet->forceFill(['balance' => $after])->save();
            $this->setRawAttributes($wallet->getAttributes(), true);

            return WalletTransaction::create([
                'user_id' => $wallet->user_id,
                'wallet_id' => $wallet->id,
                'type' => $type,
                'direction' => $direction,
                'amount' => $amount,
                'balance_before' => $before,
                'balance_after' => $after,
                'reference_type' => $reference?->getMorphClass(),
                'reference_id' => $reference?->getKey(),
                'description' => $description,
                'meta' => $meta ?: null,
                'idempotency_key' => $idempotencyKey,
                'created_at' => now(),
            ]);
        });
    }
}
