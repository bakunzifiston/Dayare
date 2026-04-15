<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class LogisticsTripOrder extends Pivot
{
    protected $table = 'logistics_trip_orders';
    public $incrementing = true;
    protected $fillable = ['trip_id', 'order_id', 'allocated_quantity', 'delivered_quantity', 'loss_quantity'];

    protected function casts(): array
    {
        return ['allocated_quantity' => 'integer', 'delivered_quantity' => 'integer', 'loss_quantity' => 'integer'];
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(LogisticsTrip::class, 'trip_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(LogisticsOrder::class, 'order_id');
    }
}

