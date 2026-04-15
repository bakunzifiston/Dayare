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
    protected $fillable = ['company_id', 'name', 'license_number', 'license_expiry', 'status'];

    protected function casts(): array
    {
        return ['license_expiry' => 'date'];
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

