<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ColdRoom extends Model
{
    use HasFactory;

    protected $fillable = [
        'facility_id',
        'name',
        'type',
        'capacity',
        'standard_id',
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'decimal:2',
        ];
    }

    public const TYPE_CHILLER = 'chiller';

    public const TYPE_FREEZER = 'freezer';

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function standard(): BelongsTo
    {
        return $this->belongsTo(ColdRoomStandard::class, 'standard_id');
    }

    public function temperatureLogs(): HasMany
    {
        return $this->hasMany(ColdRoomTemperatureLog::class);
    }

    public function violations(): HasMany
    {
        return $this->hasMany(ColdRoomViolation::class);
    }

    public function warehouseStorages(): HasMany
    {
        return $this->hasMany(WarehouseStorage::class);
    }
}
