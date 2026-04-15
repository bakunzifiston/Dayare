<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LogisticsTrip extends Model
{
    protected $table = 'logistics_trips';
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_LOADING = 'loading';
    public const STATUS_IN_TRANSIT = 'in_transit';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_FAILED = 'failed';
    public const STATUSES = [self::STATUS_SCHEDULED, self::STATUS_LOADING, self::STATUS_IN_TRANSIT, self::STATUS_DELIVERED, self::STATUS_FAILED];
    public const ACTIVE_STATUSES = [self::STATUS_SCHEDULED, self::STATUS_LOADING, self::STATUS_IN_TRANSIT];
    protected $fillable = ['company_id', 'vehicle_id', 'driver_id', 'planned_departure', 'planned_arrival', 'actual_departure', 'actual_arrival', 'status'];

    protected function casts(): array
    {
        return ['planned_departure' => 'datetime', 'planned_arrival' => 'datetime', 'actual_departure' => 'datetime', 'actual_arrival' => 'datetime'];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(LogisticsCompany::class, 'company_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(LogisticsVehicle::class, 'vehicle_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(LogisticsDriver::class, 'driver_id');
    }

    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(LogisticsOrder::class, 'logistics_trip_orders', 'trip_id', 'order_id')
            ->using(LogisticsTripOrder::class)
            ->withPivot(['id', 'allocated_quantity', 'delivered_quantity', 'loss_quantity'])
            ->withTimestamps();
    }

    public function tripOrders(): HasMany
    {
        return $this->hasMany(LogisticsTripOrder::class, 'trip_id');
    }

    public function trackingLogs(): HasMany
    {
        return $this->hasMany(LogisticsTrackingLog::class, 'trip_id');
    }

    public function complianceDocuments(): HasMany
    {
        return $this->hasMany(LogisticsComplianceDocument::class, 'trip_id');
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(LogisticsInvoice::class, 'trip_id');
    }
}

