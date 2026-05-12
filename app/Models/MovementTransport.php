<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovementTransport extends Model
{
    protected $fillable = [
        'movement_permit_id',
        'vehicle_type',
        'vehicle_number',
        'trailer_number',
        'driver_name',
        'driver_phone',
        'transporter_company',
        'route_information',
        'departure_time',
        'arrival_time',
        'estimated_duration',
        'fuel_notes',
        'emergency_contact',
        'transport_notes',
    ];

    protected function casts(): array
    {
        return [
            'departure_time' => 'datetime',
            'arrival_time' => 'datetime',
        ];
    }

    public function movementPermit(): BelongsTo
    {
        return $this->belongsTo(MovementPermit::class);
    }
}
