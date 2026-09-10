<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ButcherStockTransfer extends Model
{
    protected $fillable = [
        'business_id',
        'from_outlet_id',
        'to_outlet_id',
        'batch_id',
        'destination_batch_id',
        'quantity_kg',
        'transferred_by',
        'transferred_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity_kg' => 'decimal:3',
            'transferred_at' => 'datetime',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function fromOutlet(): BelongsTo
    {
        return $this->belongsTo(ButcherOutlet::class, 'from_outlet_id');
    }

    public function toOutlet(): BelongsTo
    {
        return $this->belongsTo(ButcherOutlet::class, 'to_outlet_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ButcherInventoryBatch::class, 'batch_id');
    }

    public function destinationBatch(): BelongsTo
    {
        return $this->belongsTo(ButcherInventoryBatch::class, 'destination_batch_id');
    }

    public function transferredByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'transferred_by');
    }
}
