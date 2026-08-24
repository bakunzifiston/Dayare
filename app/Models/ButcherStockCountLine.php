<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ButcherStockCountLine extends Model
{
    protected $fillable = [
        'stock_count_id',
        'batch_id',
        'system_weight_kg',
        'counted_weight_kg',
        'variance_kg',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'system_weight_kg' => 'decimal:3',
            'counted_weight_kg' => 'decimal:3',
            'variance_kg' => 'decimal:3',
        ];
    }

    public function stockCount(): BelongsTo
    {
        return $this->belongsTo(ButcherStockCount::class, 'stock_count_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ButcherInventoryBatch::class, 'batch_id');
    }
}
