<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SupplyRequest extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_FULFILLED = 'fulfilled';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_ACCEPTED,
        self::STATUS_REJECTED,
        self::STATUS_FULFILLED,
    ];

    protected $fillable = [
        'processor_id',
        'farmer_id',
        'destination_facility_id',
        'animal_type',
        'quantity_requested',
        'required_breed',
        'required_weight',
        'healthy_stock_required',
        'certification_required',
        'required_certification_type',
        'preferred_date',
        'status',
        'source_farm_id',
        'requested_livestock_id',
        'movement_permit_id',
    ];

    protected function casts(): array
    {
        return [
            'preferred_date' => 'date',
            'healthy_stock_required' => 'boolean',
            'certification_required' => 'boolean',
        ];
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'processor_id');
    }

    public function farmer(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'farmer_id');
    }

    public function destinationFacility(): BelongsTo
    {
        return $this->belongsTo(Facility::class, 'destination_facility_id');
    }

    public function sourceFarm(): BelongsTo
    {
        return $this->belongsTo(Farm::class, 'source_farm_id');
    }

    public function requestedLivestock(): BelongsTo
    {
        return $this->belongsTo(Livestock::class, 'requested_livestock_id');
    }

    public function animalIntake(): HasOne
    {
        return $this->hasOne(AnimalIntake::class);
    }

    public function movementPermit(): BelongsTo
    {
        return $this->belongsTo(MovementPermit::class);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
