<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LogisticsTrip extends Model
{
    protected $table = 'logistics_trips';

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_LOADED = 'loaded';

    public const STATUS_IN_TRANSIT = 'in_transit';

    public const STATUS_AT_CHECKPOINT = 'at_checkpoint';

    public const STATUS_DELAYED = 'delayed';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_SCHEDULED,
        self::STATUS_LOADED,
        self::STATUS_IN_TRANSIT,
        self::STATUS_AT_CHECKPOINT,
        self::STATUS_DELAYED,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
    ];

    /** Trip is consuming vehicle/driver capacity until finished or cancelled. */
    public const ACTIVE_STATUSES = [
        self::STATUS_SCHEDULED,
        self::STATUS_LOADED,
        self::STATUS_IN_TRANSIT,
        self::STATUS_AT_CHECKPOINT,
        self::STATUS_DELAYED,
    ];

    /**
     * Vehicle/driver considered "in execution" for utilization & active resource KPIs
     * (assigned to a trip that is loaded or in transit).
     */
    public const EXECUTION_ACTIVE_STATUSES = [
        self::STATUS_LOADED,
        self::STATUS_IN_TRANSIT,
    ];

    protected $fillable = [
        'company_id',
        'order_id',
        'origin_location_id',
        'destination_location_id',
        'vehicle_id',
        'driver_id',
        'planned_departure',
        'planned_arrival',
        'actual_departure',
        'actual_arrival',
        'status',
        'notes',
        'allocated_weight_kg',
        'delivered_weight_kg',
        'loss_weight_kg',
    ];

    protected function casts(): array
    {
        return [
            'planned_departure' => 'datetime',
            'planned_arrival' => 'datetime',
            'actual_departure' => 'datetime',
            'actual_arrival' => 'datetime',
            'allocated_weight_kg' => 'integer',
            'delivered_weight_kg' => 'integer',
            'loss_weight_kg' => 'integer',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(LogisticsCompany::class, 'company_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(LogisticsOrder::class, 'order_id');
    }

    public function originLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'origin_location_id');
    }

    public function destinationLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'destination_location_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(LogisticsVehicle::class, 'vehicle_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(LogisticsDriver::class, 'driver_id');
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
