<?php

namespace App\Support;

use App\Models\Batch;
use App\Models\Facility;
use App\Models\PostMortemInspectionItem;
use App\Models\SlaughterExecution;
use App\Models\SlaughterPlan;
use App\Models\WarehouseStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class StorablePostMortemMeat
{
    /**
     * @return Collection<int, int>
     */
    public static function accessibleBatchIds(Request $request): Collection
    {
        $facilityIds = Facility::query()
            ->whereIn('business_id', $request->user()->accessibleBusinessIds())
            ->pluck('id');

        return Batch::query()
            ->whereIn('slaughter_execution_id',
                SlaughterExecution::query()
                    ->whereIn('slaughter_plan_id',
                        SlaughterPlan::query()->whereIn('facility_id', $facilityIds)->pluck('id')
                    )
                    ->pluck('id')
            )
            ->pluck('id');
    }

    /**
     * Approved post-mortem animals not already in cold storage.
     *
     * @return Collection<int, array{
     *     id: int,
     *     label: string,
     *     batch_id: int,
     *     animal_intake_item_id: int,
     *     meat_kg: float,
     *     ear_tag: string,
     *     batch_code: string,
     * }>
     */
    public static function optionsFor(Request $request): Collection
    {
        $batchIds = self::accessibleBatchIds($request);
        if ($batchIds->isEmpty()) {
            return collect();
        }

        return PostMortemInspectionItem::query()
            ->approved()
            ->notAlreadyInColdStorage($batchIds)
            ->with(['intakeItem.slaughterExecutionItems', 'inspection.batch'])
            ->whereHas('inspection', fn ($q) => $q->whereIn('batch_id', $batchIds))
            ->latest('id')
            ->get()
            ->map(function (PostMortemInspectionItem $item): array {
                $batch = $item->inspection?->batch;
                $earTag = $item->intakeItem?->ear_tag ?: __('No tag');
                $batchCode = $batch?->batch_code ?? '—';
                $meatKg = self::meatKgForItem($item);

                return [
                    'id' => $item->id,
                    'label' => __(':tag — :batch — :kg kg (post-mortem approved)', [
                        'tag' => $earTag,
                        'batch' => $batchCode,
                        'kg' => number_format($meatKg, 2),
                    ]),
                    'batch_id' => (int) ($batch?->id ?? 0),
                    'animal_intake_item_id' => (int) $item->animal_intake_item_id,
                    'meat_kg' => $meatKg,
                    'ear_tag' => $earTag,
                    'batch_code' => $batchCode,
                ];
            })
            ->filter(fn (array $row) => $row['batch_id'] > 0)
            ->values();
    }

    public static function meatKgForItem(PostMortemInspectionItem $item): float
    {
        $afterPm = (float) ($item->carcass_weight_kg ?? 0);
        if ($afterPm > 0) {
            return round($afterPm, 2);
        }

        $intakeItem = $item->intakeItem;
        if ($intakeItem) {
            return round($intakeItem->totalMeatQuantityKg(), 2);
        }

        return 0.0;
    }

    /**
     * @return Collection<int, int>
     */
    public static function storableIdSet(Request $request): Collection
    {
        return self::optionsFor($request)->pluck('id')->map(fn ($id) => (int) $id);
    }

    public static function findStorable(Request $request, int $postMortemInspectionItemId): ?PostMortemInspectionItem
    {
        if (! self::storableIdSet($request)->contains($postMortemInspectionItemId)) {
            return null;
        }

        return PostMortemInspectionItem::query()
            ->with(['inspection.batch.certificate', 'intakeItem'])
            ->find($postMortemInspectionItemId);
    }

    /**
     * @param  list<int>  $postMortemInspectionItemIds
     * @return Collection<int, PostMortemInspectionItem>
     */
    public static function findStorableItems(Request $request, array $postMortemInspectionItemIds): Collection
    {
        $allowed = self::storableIdSet($request);
        $ids = collect($postMortemInspectionItemIds)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->filter(fn (int $id) => $allowed->contains($id))
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return PostMortemInspectionItem::query()
            ->with(['inspection.batch.certificate', 'intakeItem'])
            ->whereIn('id', $ids)
            ->get()
            ->sortBy(fn (PostMortemInspectionItem $item) => $ids->search($item->id))
            ->values();
    }
}
