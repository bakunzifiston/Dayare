<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Transport Trip – movement of meat.
 * Belongs to: Certificate, Batch (optional), Origin Facility, Destination Facility.
 */
class TransportTrip extends Model
{
    use HasFactory;

    protected $fillable = [
        'certificate_id',
        'warehouse_storage_id',
        'batch_id',
        'origin_facility_id',
        'destination_facility_id',
        'vehicle_plate_number',
        'driver_name',
        'driver_phone',
        'departure_date',
        'arrival_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'departure_date' => 'date',
            'arrival_date' => 'date',
        ];
    }

    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_TRANSIT = 'in_transit';
    public const STATUS_ARRIVED = 'arrived';
    public const STATUS_COMPLETED = 'completed';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_IN_TRANSIT,
        self::STATUS_ARRIVED,
        self::STATUS_COMPLETED,
    ];

    public function certificate(): BelongsTo
    {
        return $this->belongsTo(Certificate::class);
    }

    public function warehouseStorage(): BelongsTo
    {
        return $this->belongsTo(WarehouseStorage::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function originFacility(): BelongsTo
    {
        return $this->belongsTo(Facility::class, 'origin_facility_id');
    }

    public function destinationFacility(): BelongsTo
    {
        return $this->belongsTo(Facility::class, 'destination_facility_id');
    }

    /** TransportTrip (1) → One Delivery Confirmation */
    public function deliveryConfirmation(): HasOne
    {
        return $this->hasOne(DeliveryConfirmation::class);
    }
}
