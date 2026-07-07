<?php

namespace App\Support;

use App\Models\Batch;
use App\Models\WarehouseStorage;
use Illuminate\Support\Collection;

class BatchAnimalReleaseLookup
{
    /**
     * Latest cold-room storage record per animal, keyed by animal_intake_item_id.
     *
     * @return Collection<int, WarehouseStorage>
     */
    public static function forBatch(Batch $batch): Collection
    {
        return self::forBatches(collect([$batch->id]))->get($batch->id, collect());
    }

    /**
     * @param  Collection<int, int|string>  $batchIds
     * @return Collection<int, Collection<int, WarehouseStorage>>
     */
    public static function forBatches(Collection $batchIds): Collection
    {
        $batchIds = $batchIds->map(fn ($id) => (int) $id)->filter()->unique()->values();

        if ($batchIds->isEmpty()) {
            return collect();
        }

        $animalIdsByBatch = Batch::query()
            ->whereIn('id', $batchIds)
            ->with('items:id,batch_id,animal_intake_item_id')
            ->get()
            ->mapWithKeys(fn (Batch $batch) => [
                $batch->id => $batch->items->pluck('animal_intake_item_id')->filter()->values(),
            ]);

        $allAnimalIds = $animalIdsByBatch->flatten()->unique()->values();

        $storages = WarehouseStorage::query()
            ->with(['intakeItem', 'postMortemInspectionItem.intakeItem'])
            ->where(function ($query) use ($batchIds, $allAnimalIds): void {
                $query->whereIn('batch_id', $batchIds);

                if ($allAnimalIds->isNotEmpty()) {
                    $query->orWhereIn('animal_intake_item_id', $allAnimalIds);
                }

                $query->orWhereHas(
                    'postMortemInspectionItem.inspection',
                    fn ($inspection) => $inspection->whereIn('batch_id', $batchIds),
                );
            })
            ->orderByDesc('id')
            ->get();

        return $batchIds->mapWithKeys(function (int $batchId) use ($storages, $animalIdsByBatch): array {
            $animalIds = $animalIdsByBatch->get($batchId, collect());

            $forBatch = $storages->filter(function (WarehouseStorage $storage) use ($batchId, $animalIds): bool {
                if ((int) $storage->batch_id === $batchId) {
                    return true;
                }

                if ($storage->resolveBatchId() === $batchId) {
                    return true;
                }

                $animalId = self::resolveAnimalId($storage);

                return $animalId !== null && $animalIds->contains($animalId);
            });

            $byAnimal = $forBatch
                ->groupBy(fn (WarehouseStorage $storage) => self::resolveAnimalId($storage) ?? 0)
                ->map(fn (Collection $group) => $group->first())
                ->filter(fn (WarehouseStorage $storage, int $animalId) => $animalId > 0);

            return [$batchId => $byAnimal];
        });
    }

    public static function resolveAnimalId(WarehouseStorage $storage): ?int
    {
        if ($storage->animal_intake_item_id) {
            return (int) $storage->animal_intake_item_id;
        }

        $storage->loadMissing('postMortemInspectionItem');

        if ($storage->postMortemInspectionItem?->animal_intake_item_id) {
            return (int) $storage->postMortemInspectionItem->animal_intake_item_id;
        }

        return null;
    }
}
