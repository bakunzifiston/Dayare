<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ButcherOrderItem extends Model
{
    protected $fillable = [
        'order_id',
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

    public function order(): BelongsTo
    {
        return $this->belongsTo(ButcherOrder::class, 'order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(ButcherProduct::class, 'product_id');
    }
}
