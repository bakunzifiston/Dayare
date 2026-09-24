<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Per-animal post-mortem outcome within a batch inspection.
 */
class PostMortemInspectionItem extends Model
{
    use HasFactory;

    public const OUTCOME_APPROVED = 'approved';

    public const OUTCOME_CONDEMNED = 'condemned';

    public const OUTCOME_DEFERRED = 'deferred';

    protected $table = 'post_mortem_inspection_items';

    protected $fillable = [
        'post_mortem_inspection_id',
        'batch_item_id',
        'animal_intake_item_id',
        'outcome',
        'outcome_notes',
        'seized_part',
        'reason',
        'carcass_weight_kg',
        'condemned_weight_kg',
    ];

    protected function casts(): array
    {
        return [
            'carcass_weight_kg' => 'decimal:2',
            'condemned_weight_kg' => 'decimal:2',
        ];
    }

    /**
     * Parent post-mortem inspection record.
     */
    public function inspection(): BelongsTo
    {
        return $this->belongsTo(PostMortemInspection::class, 'post_mortem_inspection_id');
    }

    /**
     * The batch item (animal) this outcome applies to.
     */
    public function batchItem(): BelongsTo
    {
        return $this->belongsTo(BatchItem::class, 'batch_item_id');
    }

    /**
     * The underlying intake animal record.
     */
    public function intakeItem(): BelongsTo
    {
        return $this->belongsTo(AnimalIntakeItem::class, 'animal_intake_item_id');
    }

    public function warehouseStorages(): HasMany
    {
        return $this->hasMany(WarehouseStorage::class, 'post_mortem_inspection_item_id');
    }

    public function condemnedOrgans(): HasMany
    {
        return $this->hasMany(PostMortemCondemnedOrgan::class, 'post_mortem_inspection_item_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    /**
     * Organ rows for forms/display. Falls back to legacy seized_part / condemned_weight_kg.
     *
     * @return list<array{organ_name: string, weight_kg: float|null}>
     */
    public function condemnedOrganEntries(): array
    {
        $loaded = $this->relationLoaded('condemnedOrgans')
            ? $this->condemnedOrgans
            : $this->condemnedOrgans()->get();

        if ($loaded->isNotEmpty()) {
            return $loaded
                ->map(fn (PostMortemCondemnedOrgan $organ) => [
                    'organ_name' => (string) $organ->organ_name,
                    'weight_kg' => $organ->weight_kg !== null ? (float) $organ->weight_kg : null,
                ])
                ->values()
                ->all();
        }

        $organName = trim((string) ($this->seized_part ?? ''));
        $weight = $this->condemned_weight_kg !== null ? (float) $this->condemned_weight_kg : null;
        if ($organName === '' && ($weight === null || $weight <= 0)) {
            return [];
        }

        return [[
            'organ_name' => $organName,
            'weight_kg' => $weight !== null && $weight > 0 ? $weight : null,
        ]];
    }

    public function totalCondemnedWeightKg(): float
    {
        $loaded = $this->relationLoaded('condemnedOrgans')
            ? $this->condemnedOrgans
            : null;

        if ($loaded !== null && $loaded->isNotEmpty()) {
            return round((float) $loaded->sum('weight_kg'), 2);
        }

        if ($this->condemned_weight_kg !== null && (float) $this->condemned_weight_kg > 0) {
            return round((float) $this->condemned_weight_kg, 2);
        }

        return round((float) $this->condemnedOrgans()->sum('weight_kg'), 2);
    }

    /**
     * @param  Builder<PostMortemInspectionItem>  $query
     * @return Builder<PostMortemInspectionItem>
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('outcome', self::OUTCOME_APPROVED);
    }

    /**
     * @param  Builder<PostMortemInspectionItem>  $query
     * @return Builder<PostMortemInspectionItem>
     */
    public function scopeCondemned(Builder $query): Builder
    {
        return $query->where('outcome', self::OUTCOME_CONDEMNED);
    }

    /**
     * @param  Builder<PostMortemInspectionItem>  $query
     * @return Builder<PostMortemInspectionItem>
     */
    public function scopeDeferred(Builder $query): Builder
    {
        return $query->where('outcome', self::OUTCOME_DEFERRED);
    }

    /**
     * Carcass weight for display/lists. Falls back to batch meat qty when approved
     * without an explicit after-PM weight (legacy / empty submissions stored as 0).
     */
    public function displayCarcassWeightKg(): ?float
    {
        $stored = $this->carcass_weight_kg;
        if ($stored !== null && (float) $stored > 0) {
            return round((float) $stored, 2);
        }

        if ($this->outcome !== self::OUTCOME_APPROVED) {
            return null;
        }

        $batchMeat = $this->batchItem?->meat_quantity_kg;
        if ($batchMeat !== null && (float) $batchMeat > 0) {
            return round((float) $batchMeat, 2);
        }

        return null;
    }

    /**
     * Exclude animals that already have an active cold room storage record.
     *
     * @param  Builder<PostMortemInspectionItem>  $query
     * @param  Collection<int, int>|array<int, int>  $batchIds
     * @return Builder<PostMortemInspectionItem>
     */
    public function scopeNotAlreadyInColdStorage(Builder $query, Collection|array $batchIds): Builder
    {
        return $query
            ->whereDoesntHave(
                'warehouseStorages',
                fn (Builder $q) => $q->blockingRestorage()
            )
            ->whereNotExists(function ($sub): void {
                $sub->selectRaw('1')
                    ->from('warehouse_storages')
                    ->whereIn('warehouse_storages.status', WarehouseStorage::STATUSES_BLOCKING_RESTORAGE)
                    ->whereNotNull('post_mortem_inspection_items.animal_intake_item_id')
                    ->whereColumn(
                        'warehouse_storages.animal_intake_item_id',
                        'post_mortem_inspection_items.animal_intake_item_id'
                    );
            })
            ->whereNotExists(function ($sub): void {
                $sub->selectRaw('1')
                    ->from('warehouse_storages')
                    ->join(
                        'post_mortem_inspection_items as stored_pm_items',
                        'stored_pm_items.id',
                        '=',
                        'warehouse_storages.post_mortem_inspection_item_id'
                    )
                    ->whereIn('warehouse_storages.status', WarehouseStorage::STATUSES_BLOCKING_RESTORAGE)
                    ->whereNotNull('post_mortem_inspection_items.animal_intake_item_id')
                    ->whereColumn(
                        'stored_pm_items.animal_intake_item_id',
                        'post_mortem_inspection_items.animal_intake_item_id'
                    );
            });
    }
}
