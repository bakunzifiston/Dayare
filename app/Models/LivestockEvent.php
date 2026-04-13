<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LivestockEvent extends Model
{
    public const TYPE_MOVEMENT = 'movement';

    public const TYPE_SUPPLY_FULFILLMENT = 'supply_fulfillment';

    protected $fillable = [
        'farm_id',
        'livestock_id',
        'movement_permit_id',
        'event_type',
        'quantity',
        'event_date',
        'notes',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'metadata' => 'array',
        ];
    }

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function livestock(): BelongsTo
    {
        return $this->belongsTo(Livestock::class);
    }

    public function movementPermit(): BelongsTo
    {
        return $this->belongsTo(MovementPermit::class);
    }
}

