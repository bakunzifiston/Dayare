<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Warehouse (cold storage) – track post-mortem approved meat before transport.
 * Facility (type = storage) → Many WarehouseStorages.
 * Each record may represent one animal's meat after post-mortem approval.
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
        'animal_intake_item_id',
        'post_mortem_inspection_item_id',
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
            'quantity_stored' => 'decimal:2',
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

    // --- Section 2 ---

    public function coldRoom(): BelongsTo
    {
        return $this->belongsTo(ColdRoom::class, 'cold_room_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function certificate(): BelongsTo
    {
        return $this->belongsTo(Certificate::class);
    }

    public function intakeItem(): BelongsTo
    {
        return $this->belongsTo(AnimalIntakeItem::class, 'animal_intake_item_id');
    }

    public function postMortemInspectionItem(): BelongsTo
    {
        return $this->belongsTo(PostMortemInspectionItem::class, 'post_mortem_inspection_item_id');
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

    /**
     * Batch IDs linked to the user's accessible slaughter facilities.
     *
     * @return Collection<int, int>
     */
    public static function accessibleBatchIds(Request $request): Collection
    {
        return \App\Support\StorablePostMortemMeat::accessibleBatchIds($request);
    }

    public static function isAccessibleBy(Request $request, self $storage): bool
    {
        $storageFacilityIds = Facility::query()
            ->whereIn('business_id', $request->user()->accessibleBusinessIds())
            ->where('facility_type', Facility::TYPE_STORAGE)
            ->pluck('id');

        if ($storageFacilityIds->contains((int) $storage->warehouse_facility_id)) {
            return true;
        }

        if ($storage->certificate_id && self::accessibleCertificateIds($request)->contains((int) $storage->certificate_id)) {
            return true;
        }

        return self::accessibleBatchIds($request)->contains((int) $storage->batch_id);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<self>  $query
     * @return \Illuminate\Database\Eloquent\Builder<self>
     */
    public function scopeForColdRoomUser($query, Request $request)
    {
        $storageFacilityIds = Facility::query()
            ->whereIn('business_id', $request->user()->accessibleBusinessIds())
            ->where('facility_type', Facility::TYPE_STORAGE)
            ->pluck('id');

        return $query->whereIn('warehouse_facility_id', $storageFacilityIds);
    }
}
