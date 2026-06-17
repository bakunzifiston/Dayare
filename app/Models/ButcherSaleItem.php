<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ButcherSaleItem extends Model
{
    protected $fillable = [
        'sale_id',
        'cut_output_id',
        'product_id',
        'quantity_kg',
        'quantity_units',
        'unit_price',
        'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'quantity_kg' => 'decimal:3',
            'quantity_units' => 'integer',
            'unit_price' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(ButcherSale::class, 'sale_id');
    }

    public function cutOutput(): BelongsTo
    {
        return $this->belongsTo(ButcherCutOutput::class, 'cut_output_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(ButcherProduct::class, 'product_id');
    }
}
