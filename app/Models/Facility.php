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
    public const TYPE_OTHER = 'Other';

    public const TYPES = [
        self::TYPE_SLAUGHTERHOUSE,
        self::TYPE_BUTCHERY,
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

    /** Facility (1) → Many Inspectors */
    public function inspectors(): HasMany
    {
        return $this->hasMany(Inspector::class);
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
}
