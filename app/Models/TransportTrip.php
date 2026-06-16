<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Transport Trip – movement of certified meat.
 * Belongs to: Certificate (required), Batch (derived), Origin Facility, Destination Facility.
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
        'destination_name',
        'destination_country',
        'destination_address',
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

    public function isExternalDestination(): bool
    {
        return $this->destination_facility_id === null;
    }

    public function getDestinationDisplayAttribute(): string
    {
        if ($this->destinationFacility) {
            return $this->destinationFacility->facility_name;
        }

        $parts = array_filter([$this->destination_name, $this->destination_country]);

        return implode(' — ', $parts) ?: ($this->destination_name ?? __('External destination'));
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public static function normalizeDestinationAttributes(array $attributes): array
    {
        $facilityId = $attributes['destination_facility_id'] ?? null;
        if ($facilityId !== null && $facilityId !== '') {
            $attributes['destination_facility_id'] = (int) $facilityId;
            $attributes['destination_name'] = null;
            $attributes['destination_country'] = null;
            $attributes['destination_address'] = null;
        } else {
            $attributes['destination_facility_id'] = null;
        }

        return $attributes;
    }

    /** TransportTrip (1) → One Delivery Confirmation */
    public function deliveryConfirmation(): HasOne
    {
        return $this->hasOne(DeliveryConfirmation::class);
    }
}
