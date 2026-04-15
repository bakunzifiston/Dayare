<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LogisticsCompany extends Model
{
    protected $table = 'logistics_companies';

    protected $fillable = [
        'business_id',
        'name',
        'registration_number',
        'tax_id',
        'license_type',
        'license_expiry_date',
        'operating_regions',
        'contact_person',
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
