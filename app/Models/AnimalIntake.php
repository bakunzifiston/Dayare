<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Animal Origin (Intake) – record where animals come from before slaughter.
 * Must happen BEFORE SlaughterPlan. Facility (slaughterhouse) → Many AnimalIntakes.
 */
class AnimalIntake extends Model
{
    use HasFactory;

    protected $fillable = [
        'facility_id',
        'supplier_id',
        'contract_id',
        'intake_date',
        'supplier_firstname',
        'supplier_lastname',
        'supplier_contact',
        'farm_name',
        'farm_registration_number',
        'country_id',
        'province_id',
        'district_id',
        'sector_id',
        'cell_id',
        'village_id',
        'species',
        'number_of_animals',
        'unit_price',
        'total_price',
        'animal_identification_numbers',
        'transport_vehicle_plate',
        'driver_name',
        'animal_health_certificate_number',
        'health_certificate_issue_date',
        'health_certificate_expiry_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'intake_date' => 'date',
            'health_certificate_issue_date' => 'date',
            'health_certificate_expiry_date' => 'date',
        ];
    }

    public const STATUS_RECEIVED = 'received';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public const STATUSES = [
        self::STATUS_RECEIVED,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
    ];

    public const SPECIES_CATTLE = 'Cattle';
    public const SPECIES_GOAT = 'Goat';
    public const SPECIES_SHEEP = 'Sheep';
    public const SPECIES_PIG = 'Pig';
    public const SPECIES_OTHER = 'Other';

    public const SPECIES_OPTIONS = [
        self::SPECIES_CATTLE,
        self::SPECIES_GOAT,
        self::SPECIES_SHEEP,
        self::SPECIES_PIG,
        self::SPECIES_OTHER,
    ];

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
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

    public function slaughterPlans(): HasMany
    {
        return $this->hasMany(SlaughterPlan::class);
    }

    /** If health certificate is expired → block SlaughterPlan creation */
    public function isHealthCertificateExpired(): bool
    {
        if (! $this->health_certificate_expiry_date) {
            return false;
        }
        return $this->health_certificate_expiry_date->isPast();
    }

    /** Total number of animals already scheduled for slaughter from this intake */
    public function totalScheduledForSlaughter(): int
    {
        return (int) $this->slaughterPlans()->sum('number_of_animals_scheduled');
    }

    /** Remaining animals that can still be scheduled (number_of_animals - total scheduled) */
    public function remainingAnimalsAvailable(): int
    {
        return max(0, $this->number_of_animals - $this->totalScheduledForSlaughter());
    }
}
