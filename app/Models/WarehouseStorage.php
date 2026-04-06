<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

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
        'cold_room_id',
        'batch_id',
        'certificate_id',
        'entry_date',
        'storage_location',
        'temperature_at_entry',
        'quantity_stored',
        'quantity_unit',
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

    /** Display label for quantity_unit (from configured Unit name, or Demand legacy label, or code). */
    public function getQuantityUnitLabelAttribute(): string
    {
        $unit = Unit::where('code', $this->quantity_unit)->first();
        if ($unit) {
            return $unit->name;
        }

        return Demand::QUANTITY_UNITS[$this->quantity_unit] ?? $this->quantity_unit;
    }

    public function warehouseFacility(): BelongsTo
    {
        return $this->belongsTo(Facility::class, 'warehouse_facility_id');
    }

    public function coldRoom(): BelongsTo
    {
        return $this->belongsTo(ColdRoom::class);
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

    /**
     * Certificate IDs the signed-in user may use for cold room (warehouse) storage.
     */
    public static function accessibleCertificateIds(Request $request): Collection
    {
        $facilityIds = Facility::whereIn('business_id', $request->user()->accessibleBusinessIds())
            ->pluck('id');
        $batchIds = Batch::whereIn('slaughter_execution_id',
            SlaughterExecution::whereIn('slaughter_plan_id',
                SlaughterPlan::whereIn('facility_id', $facilityIds)->pluck('id')
            )->pluck('id')
        )->pluck('id');

        return Certificate::where(function ($q) use ($batchIds, $facilityIds) {
            $q->whereIn('batch_id', $batchIds)
                ->orWhere(fn ($q2) => $q2->whereNull('batch_id')->whereIn('facility_id', $facilityIds));
        })->pluck('id');
    }
}
