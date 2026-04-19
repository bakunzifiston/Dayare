<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LogisticsCompany extends Model
{
    public const TYPE_INDIVIDUAL = 'individual';

    public const TYPE_SHARED_COMPANY = 'shared_company';

    public const COMPANY_TYPES = [
        self::TYPE_INDIVIDUAL,
        self::TYPE_SHARED_COMPANY,
    ];

    protected $table = 'logistics_companies';

    protected $fillable = [
        'business_id',
        'company_type',
        'name',
        'registration_number',
        'tax_id',
        'license_type',
        'license_expiry_date',
        'operating_regions',
        'contact_person',
        'country_id',
        'province_id',
        'district_id',
        'sector_id',
        'cell_id',
        'village_id',
    ];

    protected function casts(): array
    {
        return [
            'license_expiry_date' => 'date',
            'operating_regions' => 'array',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(LogisticsCompanyMember::class, 'logistics_company_id');
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

    public function isSharedCompany(): bool
    {
        return $this->company_type === self::TYPE_SHARED_COMPANY;
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(LogisticsVehicle::class, 'company_id');
    }

    public function drivers(): HasMany
    {
        return $this->hasMany(LogisticsDriver::class, 'company_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(LogisticsOrder::class, 'company_id');
    }

    public function trips(): HasMany
    {
        return $this->hasMany(LogisticsTrip::class, 'company_id');
    }

    public function hasValidLicense(?CarbonInterface $date = null): bool
    {
        $on = $date ?? now();

        return $this->license_expiry_date !== null && $this->license_expiry_date->gte($on);
    }
}
