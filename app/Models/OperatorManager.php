<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OperatorManager extends Model
{
    use HasFactory;

    protected $fillable = [
        'facility_id',
        'first_name',
        'last_name',
        'national_id',
        'phone_number',
        'email',
        'dob',
        'nationality',
        'country_id',
        'province_id',
        'district_id',
        'sector_id',
        'cell_id',
        'village_id',
        'country',
        'district',
        'sector',
        'cell',
        'village',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'dob' => 'date',
        ];
    }

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_INACTIVE,
    ];

    /** Operator Manager belongs to one Facility. */
    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function countryDivision(): BelongsTo
    {
        return $this->belongsTo(AdministrativeDivision::class, 'country_id');
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(AdministrativeDivision::class, 'province_id');
    }

    public function districtDivision(): BelongsTo
    {
        return $this->belongsTo(AdministrativeDivision::class, 'district_id');
    }

    public function sectorDivision(): BelongsTo
    {
        return $this->belongsTo(AdministrativeDivision::class, 'sector_id');
    }

    public function cellDivision(): BelongsTo
    {
        return $this->belongsTo(AdministrativeDivision::class, 'cell_id');
    }

    public function villageDivision(): BelongsTo
    {
        return $this->belongsTo(AdministrativeDivision::class, 'village_id');
    }

    /** Location string from divisions or legacy text fields. */
    public function getLocationLineAttribute(): string
    {
        if ($this->country_id && $this->relationLoaded('countryDivision') && $this->countryDivision) {
            $parts = array_filter([
                $this->countryDivision->name ?? null,
                $this->province?->name ?? null,
                $this->districtDivision?->name ?? null,
                $this->sectorDivision?->name ?? null,
                $this->cellDivision?->name ?? null,
                $this->villageDivision?->name ?? null,
            ]);
            return implode(', ', $parts) ?: '—';
        }
        $parts = array_filter([$this->country, $this->district, $this->sector, $this->cell, $this->village]);
        return implode(', ', $parts) ?: '—';
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}
