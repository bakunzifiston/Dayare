<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Warehouse (cold storage) – track certified meat batches before transport.
 * Facility (type = storage) → Many WarehouseStorages.
 * Batch (1) → One WarehouseStorage. Certificate required before storage.
 */
class WarehouseStorage extends Model
{
    use HasFactory;

    protected $table = 'warehouse_storages';

    protected $fillable = [
        'warehouse_facility_id',
        'batch_id',
        'certificate_id',
        'entry_date',
        'storage_location',
        'temperature_at_entry',
        'quantity_stored',
        'status',
        'released_date',
    ];

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'released_date' => 'date',
        ];
    }

    public const STATUS_IN_STORAGE = 'in_storage';
    public const STATUS_RELEASED = 'released';
    public const STATUS_DISPOSED = 'disposed';

    public const STATUSES = [
        self::STATUS_IN_STORAGE,
        self::STATUS_RELEASED,
        self::STATUS_DISPOSED,
    ];

    public function warehouseFacility(): BelongsTo
    {
        return $this->belongsTo(Facility::class, 'warehouse_facility_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function certificate(): BelongsTo
    {
        return $this->belongsTo(Certificate::class);
    }

    public function temperatureLogs(): HasMany
    {
        return $this->hasMany(TemperatureLog::class);
    }

    public function transportTrips(): HasMany
    {
        return $this->hasMany(TransportTrip::class);
    }

    public function isReleased(): bool
    {
        return $this->status === self::STATUS_RELEASED;
    }

    public function isInStorage(): bool
    {
        return $this->status === self::STATUS_IN_STORAGE;
    }
}
