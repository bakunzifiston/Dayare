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
    ];

    protected function casts(): array
    {
        return [
            'carcass_weight_kg' => 'decimal:2',
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
