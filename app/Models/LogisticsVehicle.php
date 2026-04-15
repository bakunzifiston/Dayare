<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LogisticsVehicle extends Model
{
    protected $table = 'logistics_vehicles';
    public const STATUS_AVAILABLE = 'available';
    public const STATUS_IN_USE = 'in_use';
    public const STATUS_MAINTENANCE = 'maintenance';
    public const STATUSES = [self::STATUS_AVAILABLE, self::STATUS_IN_USE, self::STATUS_MAINTENANCE];

    protected $fillable = ['company_id', 'plate_number', 'type', 'max_weight', 'max_units', 'status'];

    protected function casts(): array
    {
        return ['max_weight' => 'decimal:2', 'max_units' => 'integer'];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(LogisticsCompany::class, 'company_id');
    }

    public function trips(): HasMany
    {
        return $this->hasMany(LogisticsTrip::class, 'vehicle_id');
    }
}

