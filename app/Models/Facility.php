<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Facility extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'facility_name',
        'facility_type',
        'district',
        'sector',
        'country_id',
        'province_id',
        'district_id',
        'sector_id',
        'cell_id',
        'village_id',
        'gps',
        'license_number',
        'license_issue_date',
        'license_expiry_date',
        'daily_capacity',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'license_issue_date' => 'date',
            'license_expiry_date' => 'date',
        ];
    }

    public const TYPE_SLAUGHTERHOUSE = 'Slaughterhouse';

    public const TYPE_BUTCHERY = 'Butchery';

    public const TYPE_STORAGE = 'storage';

    public const TYPE_OTHER = 'Other';

    public const TYPES = [
        self::TYPE_SLAUGHTERHOUSE,
        self::TYPE_BUTCHERY,
        self::TYPE_STORAGE,
        self::TYPE_OTHER,
    ];

    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_SUSPENDED,
    ];

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

    public function districtDivision(): BelongsTo
    {
        return $this->belongsTo(AdministrativeDivision::class, 'district_id');
    }

    public function sectorDivision(): BelongsTo
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

    /** Location string from divisions (Rwanda) or fallback to legacy district, sector */
    public function getLocationDisplayAttribute(): string
    {
        $parts = array_filter([
            $this->village?->name,
            $this->cell?->name,
            $this->sectorDivision?->name,
            $this->districtDivision?->name,
            $this->province?->name,
        ]);
        if ($parts !== []) {
            return implode(', ', $parts);
        }
        $legacyDistrict = $this->getRawOriginal('district');
        $legacySector = $this->getRawOriginal('sector');
        if ($legacyDistrict || $legacySector) {
            return trim(($legacyDistrict ?? '').', '.($legacySector ?? ''), ', ');
        }

        return '—';
    }

    /** Facility (1) → Many Inspectors */
    public function inspectors(): HasMany
    {
        return $this->hasMany(Inspector::class);
    }

    /** Facility (1) → Many Employees (assigned to this facility) */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isLicenseExpired(): bool
    {
        return $this->license_expiry_date && $this->license_expiry_date->isPast();
    }

    /** Facility (1) → Many Slaughter Sessions (SlaughterPlan) */
    public function slaughterPlans(): HasMany
    {
        return $this->hasMany(SlaughterPlan::class);
    }

    /** Facility (1) → Many Certificates */
    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    public function transportTripsOrigin(): HasMany
    {
        return $this->hasMany(TransportTrip::class, 'origin_facility_id');
    }

    public function transportTripsDestination(): HasMany
    {
        return $this->hasMany(TransportTrip::class, 'destination_facility_id');
    }

    public function deliveryConfirmationsReceived(): HasMany
    {
        return $this->hasMany(DeliveryConfirmation::class, 'receiving_facility_id');
    }

    /** Facility (type = storage) → Many WarehouseStorages */
    public function warehouseStorages(): HasMany
    {
        return $this->hasMany(WarehouseStorage::class, 'warehouse_facility_id');
    }

    public function coldRooms(): HasMany
    {
        return $this->hasMany(ColdRoom::class, 'facility_id');
    }

    public function isStorage(): bool
    {
        return $this->facility_type === self::TYPE_STORAGE;
    }

    /**
     * Parse free-text GPS (e.g. "-1.9536, 30.0606") into lat/lng for maps.
     * Accepts comma or semicolon separators; strips degree symbols.
     *
     * @return array{lat: float, lng: float}|null
     */
    public static function parseGpsToLatLng(?string $gps): ?array
    {
        if ($gps === null || trim($gps) === '') {
            return null;
        }

        $clean = str_replace(['°', 'N', 'S', 'E', 'W'], '', $gps);
        $clean = preg_replace('/\s+/', ' ', trim($clean));
        if ($clean === '') {
            return null;
        }

        $parts = preg_split('/\s*[,;]\s*/', $clean, 3);
        if (count($parts) < 2) {
            return null;
        }

        $a = filter_var($parts[0], FILTER_VALIDATE_FLOAT);
        $b = filter_var($parts[1], FILTER_VALIDATE_FLOAT);
        if ($a === false || $b === false) {
            return null;
        }

        // Convention: latitude first (|lat| ≤ 90), longitude second (|lng| ≤ 180).
        if (abs($a) <= 90 && abs($b) <= 180) {
            return ['lat' => (float) $a, 'lng' => (float) $b];
        }

        // Swapped order if user entered lng, lat
        if (abs($b) <= 90 && abs($a) <= 180) {
            return ['lat' => (float) $b, 'lng' => (float) $a];
        }

        return null;
    }

    /** Facility (slaughterhouse) → Many AnimalIntakes */
    public function animalIntakes(): HasMany
    {
        return $this->hasMany(AnimalIntake::class);
    }

    public function demandsAsDestination(): HasMany
    {
        return $this->hasMany(Demand::class, 'destination_facility_id');
    }
}
