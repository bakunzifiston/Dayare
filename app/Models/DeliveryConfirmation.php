<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Delivery Confirmation – confirm meat received.
 * TransportTrip (1) → One Delivery Confirmation.
 */
class DeliveryConfirmation extends Model
{
    use HasFactory;

    protected $fillable = [
        'transport_trip_id',
        'receiving_facility_id',
        'received_quantity',
        'received_date',
        'receiver_name',
        'confirmation_status',
    ];

    protected function casts(): array
    {
        return [
            'received_date' => 'date',
        ];
    }

    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_DISPUTED = 'disputed';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_CONFIRMED,
        self::STATUS_DISPUTED,
    ];

    public function transportTrip(): BelongsTo
    {
        return $this->belongsTo(TransportTrip::class);
    }

    public function receivingFacility(): BelongsTo
    {
        return $this->belongsTo(Facility::class, 'receiving_facility_id');
    }
}
