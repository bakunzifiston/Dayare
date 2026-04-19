<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LogisticsDriver extends Model
{
    protected $table = 'logistics_drivers';

    public const STATUS_AVAILABLE = 'available';

    public const STATUS_ASSIGNED = 'assigned';

    public const STATUSES = [self::STATUS_AVAILABLE, self::STATUS_ASSIGNED];

    protected $fillable = [
        'company_id',
        'name',
        'first_name',
        'last_name',
        'phone_number',
        'national_id_or_license_id',
        'gender',
        'dob',
        'country_id',
        'province_id',
        'district_id',
        'sector_id',
        'cell_id',
        'village_id',
        'photo_path',
        'license_number',
        'license_category',
        'license_expiry',
        'experience_years',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'dob' => 'date',
            'license_expiry' => 'date',
            'experience_years' => 'integer',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(LogisticsCompany::class, 'company_id');
    }

    public function trips(): HasMany
    {
        return $this->hasMany(LogisticsTrip::class, 'driver_id');
    }

    public function hasValidLicense(?CarbonInterface $date = null): bool
    {
        $on = $date ?? now();

        return $this->license_expiry !== null && $this->license_expiry->gte($on);
    }
}
