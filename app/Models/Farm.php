<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Farm extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const STATUS_SUSPENDED = 'suspended';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_INACTIVE,
        self::STATUS_SUSPENDED,
    ];

    public const LAND_OWNERSHIP_OWNED = 'owned';

    public const LAND_OWNERSHIP_LEASED = 'leased';

    public const LAND_OWNERSHIP_COMMUNAL = 'communal';

    public const LAND_OWNERSHIP_GOVERNMENT = 'government';

    public const LAND_OWNERSHIP_OTHER = 'other';

    /** @var list<string> */
    public const LAND_OWNERSHIP_TYPES = [
        self::LAND_OWNERSHIP_OWNED,
        self::LAND_OWNERSHIP_LEASED,
        self::LAND_OWNERSHIP_COMMUNAL,
        self::LAND_OWNERSHIP_GOVERNMENT,
        self::LAND_OWNERSHIP_OTHER,
    ];

    protected $fillable = [
        'business_id',
        'country_id',
        'name',
        'registration_number',
        'province_id',
        'district_id',
        'sector_id',
        'cell_id',
        'village_id',
        'gps_latitude',
        'gps_longitude',
        'farm_size_hectares',
        'land_ownership_type',
        'registration_date',
        'animal_types',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'animal_types' => 'array',
            'registration_date' => 'date',
            'gps_latitude' => 'float',
            'gps_longitude' => 'float',
            'farm_size_hectares' => 'float',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(AdministrativeDivision::class, 'country_id');
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(AdministrativeDivision::class, 'province_id');
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(AdministrativeDivision::class, 'district_id');
    }

    public function sector(): BelongsTo
    {
        return $this->belongsTo(AdministrativeDivision::class, 'sector_id');
    }

    public function cell(): BelongsTo
    {
        return $this->belongsTo(AdministrativeDivision::class, 'cell_id');
    }

    public function village(): BelongsTo
    {
        return $this->belongsTo(AdministrativeDivision::class, 'village_id');
    }

    public function livestock(): HasMany
    {
        return $this->hasMany(Livestock::class);
    }

    public function healthRecords(): HasMany
    {
        return $this->hasMany(AnimalHealthRecord::class);
    }

    public function movementPermits(): HasMany
    {
        return $this->hasMany(MovementPermit::class, 'source_farm_id');
    }

    public function livestockEvents(): HasMany
    {
        return $this->hasMany(LivestockEvent::class);
    }

    public function healthCertificates(): HasMany
    {
        return $this->hasMany(FarmerHealthCertificate::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}
