<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MovementPermit extends Model
{
    protected $fillable = [
        'permit_number',
        'farmer_id',
        'source_farm_id',
        'destination_district_id',
        'destination_sector_id',
        'destination_cell_id',
        'destination_village_id',
        'transport_mode',
        'vehicle_plate',
        'issue_date',
        'expiry_date',
        'issued_by',
        'file_path',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'expiry_date' => 'date',
        ];
    }

    public function farmer(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'farmer_id');
    }

    public function sourceFarm(): BelongsTo
    {
        return $this->belongsTo(Farm::class, 'source_farm_id');
    }

    public function destinationDistrict(): BelongsTo
    {
        return $this->belongsTo(AdministrativeDivision::class, 'destination_district_id');
    }

    public function destinationSector(): BelongsTo
    {
        return $this->belongsTo(AdministrativeDivision::class, 'destination_sector_id');
    }

    public function destinationCell(): BelongsTo
    {
        return $this->belongsTo(AdministrativeDivision::class, 'destination_cell_id');
    }

    public function destinationVillage(): BelongsTo
    {
        return $this->belongsTo(AdministrativeDivision::class, 'destination_village_id');
    }

    public function animals(): HasMany
    {
        return $this->hasMany(MovementPermitAnimal::class);
    }

    public function livestockEvents(): HasMany
    {
        return $this->hasMany(LivestockEvent::class);
    }

    public function isValidOn(\Carbon\CarbonInterface $date): bool
    {
        return $this->issue_date !== null
            && $this->expiry_date !== null
            && $this->issue_date->lte($date)
            && $this->expiry_date->gte($date);
    }
}

