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
        'client_id',
        'received_quantity',
        'received_date',
        'receiver_name',
        'receiver_country',
        'receiver_address',
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

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /** True when delivery was to a non-registered facility (e.g. external / international). */
    public function isExternalRecipient(): bool
    {
        return $this->receiving_facility_id === null;
    }

    /** Display label for where product was received (facility, client, or name/country). */
    public function getReceiverDisplayAttribute(): string
    {
        if ($this->receiving_facility_id && $this->relationLoaded('receivingFacility') && $this->receivingFacility) {
            return $this->receivingFacility->facility_name;
        }
        if ($this->client_id && $this->relationLoaded('client') && $this->client) {
            return $this->client->display_name;
        }
        $parts = array_filter([$this->receiver_name, $this->receiver_country ?? null]);
        return implode(' — ', $parts) ?: ($this->receiver_name ?? '—');
    }
}
