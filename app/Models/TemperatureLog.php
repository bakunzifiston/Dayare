<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TemperatureLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_storage_id',
        'recorded_temperature',
        'recorded_at',
        'recorded_by',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'recorded_at' => 'datetime',
        ];
    }

    public const STATUS_NORMAL = 'normal';
    public const STATUS_WARNING = 'warning';
    public const STATUS_CRITICAL = 'critical';

    public const STATUSES = [
        self::STATUS_NORMAL,
        self::STATUS_WARNING,
        self::STATUS_CRITICAL,
    ];

    public function warehouseStorage(): BelongsTo
    {
        return $this->belongsTo(WarehouseStorage::class);
    }
}
