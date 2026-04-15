<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogisticsInvoice extends Model
{
    protected $table = 'logistics_invoices';
    public const PAYMENT_PENDING = 'pending';
    public const PAYMENT_PAID = 'paid';
    public const PAYMENT_STATUSES = [self::PAYMENT_PENDING, self::PAYMENT_PAID];
    protected $fillable = ['trip_id', 'base_cost', 'cost_per_km', 'distance_km', 'cost_per_unit', 'extra_charges', 'total_amount', 'payment_status'];

    protected function casts(): array
    {
        return [
            'base_cost' => 'decimal:2',
            'cost_per_km' => 'decimal:2',
            'distance_km' => 'decimal:2',
            'cost_per_unit' => 'decimal:2',
            'extra_charges' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(LogisticsTrip::class, 'trip_id');
    }
}

