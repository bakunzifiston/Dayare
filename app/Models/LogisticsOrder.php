<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LogisticsOrder extends Model
{
    protected $table = 'logistics_orders';
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUSES = [self::STATUS_PENDING, self::STATUS_APPROVED, self::STATUS_REJECTED];
    public const PRIORITY_LOW = 'low';
    public const PRIORITY_NORMAL = 'normal';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITIES = [self::PRIORITY_LOW, self::PRIORITY_NORMAL, self::PRIORITY_HIGH];
    protected $fillable = ['company_id', 'client_id', 'pickup_location', 'delivery_location', 'species', 'quantity', 'weight', 'requested_date', 'priority', 'status'];

    protected function casts(): array
    {
        return ['quantity' => 'integer', 'weight' => 'decimal:2', 'requested_date' => 'date'];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(LogisticsCompany::class, 'company_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'client_id');
    }

    public function trips(): BelongsToMany
    {
        return $this->belongsToMany(LogisticsTrip::class, 'logistics_trip_orders', 'order_id', 'trip_id')
            ->using(LogisticsTripOrder::class)
            ->withPivot(['allocated_quantity', 'delivered_quantity', 'loss_quantity'])
            ->withTimestamps();
    }

    public function tripOrderAllocations(): HasMany
    {
        return $this->hasMany(LogisticsTripOrder::class, 'order_id');
    }
}

