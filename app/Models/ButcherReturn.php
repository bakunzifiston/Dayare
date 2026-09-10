<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ButcherReturn extends Model
{
    protected $fillable = [
        'business_id',
        'sale_item_id',
        'cut_output_id',
        'quantity_kg',
        'reason',
        'processed_by',
        'processed_at',
        'credit_reversed',
    ];

    protected function casts(): array
    {
        return [
            'quantity_kg' => 'decimal:3',
            'credit_reversed' => 'decimal:2',
            'processed_at' => 'datetime',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(ButcherSaleItem::class, 'sale_item_id');
    }

    public function cutOutput(): BelongsTo
    {
        return $this->belongsTo(ButcherCutOutput::class, 'cut_output_id');
    }

    public function processedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
