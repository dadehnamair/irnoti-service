<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One line of an {@see Invoice}. amount is kept as quantity × unit_price. */
class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id',
        'description',
        'quantity',
        'unit_price',
        'amount',
        'sort',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'integer',
        'amount' => 'integer',
        'sort' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (InvoiceItem $item) {
            $item->amount = max(0, (int) $item->quantity) * max(0, (int) $item->unit_price);
        });

        static::saved(fn (InvoiceItem $item) => $item->invoice?->recalculateTotals());
        static::deleted(fn (InvoiceItem $item) => $item->invoice?->recalculateTotals());
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
