<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Farm extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_INACTIVE,
    ];

    protected $fillable = [
        'business_id',
        'country_id',
        'name',
        'province_id',
        'district_id',
        'sector_id',
        'cell_id',
        'village_id',
        'animal_types',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'animal_types' => 'array',
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

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}
