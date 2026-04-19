<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LogisticsVehicle extends Model
{
    protected $table = 'logistics_vehicles';

    public const TYPE_REFRIGERATED_TRUCK = 'Refrigerated Truck';

    public const TYPE_LIVESTOCK_TRAILER = 'Livestock Trailer';

    public const TYPE_STANDARD_TRUCK = 'Standard Truck';

    public const TYPES = [
        self::TYPE_REFRIGERATED_TRUCK,
        self::TYPE_LIVESTOCK_TRAILER,
        self::TYPE_STANDARD_TRUCK,
    ];

    public const CAPACITY_UNIT_KILOGRAMS = 'kg';

    public const CAPACITY_UNIT_HEADS = 'heads';

    public const CAPACITY_UNIT_TONS = 'tons';

    public const CAPACITY_UNITS = [
        self::CAPACITY_UNIT_KILOGRAMS,
        self::CAPACITY_UNIT_HEADS,
        self::CAPACITY_UNIT_TONS,
    ];

    public const FEATURE_GPS_TRACKING = 'gps_tracking';

    public const FEATURE_TEMPERATURE_CONTROL = 'temperature_control';

    public const FEATURES = [
        self::FEATURE_GPS_TRACKING,
        self::FEATURE_TEMPERATURE_CONTROL,
    ];

    public const STATUS_AVAILABLE = 'available';

    public const STATUS_IN_USE = 'in_use';

    public const STATUS_MAINTENANCE = 'maintenance';

    public const STATUSES = [self::STATUS_AVAILABLE, self::STATUS_IN_USE, self::STATUS_MAINTENANCE];

    protected $fillable = [
        'company_id',
        'plate_number',
        'type',
        'capacity_value',
        'capacity_unit',
        'vehicle_features',
        'max_weight',
        'max_units',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'capacity_value' => 'decimal:2',
            'vehicle_features' => 'array',
            'max_weight' => 'decimal:2',
            'max_units' => 'integer',
        ];
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
