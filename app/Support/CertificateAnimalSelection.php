<?php

namespace App\Support;

use App\Models\Batch;
use App\Models\BatchItem;
use App\Models\Certificate;
use App\Models\WarehouseStorage;
use Illuminate\Support\Collection;

class CertificateAnimalSelection
{
    /**
     * Released, post-mortem approved animals not yet on another certificate.
     *
     * @param  int|null  $ignoreCertificateId  When updating, keep animals already on this certificate selectable.
     * @return Collection<int, array{
     *     animal_intake_item_id: int,
     *     ear_tag: string,
     *     species: string,
     *     sex: string,
     *     released_kg: float,
     *     carcass_weight_kg: float|null,
     *     warehouse_storage_id: int,
     * }>
     */
    public static function certifiableAnimals(Batch $batch, ?int $ignoreCertificateId = null): Collection
    {
        if (! $batch->hasReleasedColdRoomStorage()) {
            return collect();
        }

        if (! $batch->hasPerAnimalData()) {
            if ($batch->certificates()->when(
                $ignoreCertificateId,
                fn ($q) => $q->whereKeyNot($ignoreCertificateId)
            )->exists()) {
                return collect();
            }

            return collect();
        }

        $batch->loadMissing(['items.intakeItem', 'items.postMortemOutcome', 'certificates']);
        $releaseByAnimal = BatchAnimalReleaseLookup::forBatch($batch);
        $certifiedIds = self::certifiedAnimalIds($batch, $ignoreCertificateId);

        return $batch->items
            ->filter(function (BatchItem $item) use ($releaseByAnimal, $certifiedIds): bool {
                $animalId = (int) $item->animal_intake_item_id;
                if ($certifiedIds->contains($animalId)) {
                    return false;
                }

                $outcome = $item->postMortemOutcome?->outcome;
                if ($outcome !== 'approved') {
                    return false;
                }

                $storage = $releaseByAnimal->get($animalId);

                return $storage !== null && $storage->isReleased();
            })
            ->map(function (BatchItem $item) use ($releaseByAnimal): array {
                $storage = $releaseByAnimal->get((int) $item->animal_intake_item_id);

                return [
                    'animal_intake_item_id' => (int) $item->animal_intake_item_id,
                    'ear_tag' => (string) ($item->intakeItem?->ear_tag ?? '—'),
                    'species' => (string) ($item->intakeItem?->species ?? '—'),
                    'sex' => (string) ($item->intakeItem?->sex ?? ''),
                    'released_kg' => (float) ($storage?->quantity_stored ?? 0),
                    'carcass_weight_kg' => $item->postMortemOutcome?->carcass_weight_kg !== null
                        ? (float) $item->postMortemOutcome->carcass_weight_kg
                        : null,
                    'warehouse_storage_id' => (int) ($storage?->id ?? 0),
                ];
            })
            ->filter(fn (array $row) => $row['warehouse_storage_id'] > 0)
            ->values();
    }

    /**
     * @return Collection<int, int>
     */
    public static function certifiedAnimalIds(Batch $batch, ?int $ignoreCertificateId = null): Collection
    {
        $batch->loadMissing('certificates');

        return $batch->certificates
            ->when(
                $ignoreCertificateId,
                fn (Collection $certificates) => $certificates->reject(
                    fn (Certificate $certificate) => (int) $certificate->id === (int) $ignoreCertificateId
                )
            )
            ->flatMap(fn (Certificate $certificate) => self::certificateAnimalIds($certificate))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
    }

    /**
     * @param  list<int|string>  $selectedIds
     */
    public static function validateSelection(Batch $batch, array $selectedIds, ?int $ignoreCertificateId = null): ?string
    {
        $selectedIds = collect($selectedIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values();

        if ($selectedIds->isEmpty()) {
            return __('Select at least one animal for this certificate.');
        }

        $available = self::certifiableAnimals($batch, $ignoreCertificateId)
            ->pluck('animal_intake_item_id')
            ->map(fn ($id) => (int) $id);

        // Animals already on this certificate stay valid when editing (form may omit checkboxes).
        if ($ignoreCertificateId !== null) {
            $certificate = Certificate::query()->find($ignoreCertificateId);
            if ($certificate !== null) {
                $available = $available
                    ->merge(self::certificateAnimalIds($certificate))
                    ->unique()
                    ->values();
            }
        }

        if ($available->isEmpty() && ! $batch->hasPerAnimalData()) {
            if ($batch->certificates()->when(
                $ignoreCertificateId,
                fn ($q) => $q->whereKeyNot($ignoreCertificateId)
            )->exists()) {
                return __('This batch already has a certificate.');
            }

            return null;
        }

        $invalid = $selectedIds->diff($available);
        if ($invalid->isNotEmpty()) {
            return __('One or more selected animals are not released, not approved, or already certified.');
        }

        return null;
    }

    /**
     * Explicit animal selection stored on the certificate (empty for legacy rows).
     *
     * @return Collection<int, int>
     */
    public static function explicitCertificateAnimalIds(Certificate $certificate): Collection
    {
        return collect($certificate->animal_intake_item_ids ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values();
    }

    /**
     * Animals covered by a certificate (explicit selection or full batch for legacy rows).
     *
     * @return Collection<int, int>
     */
    public static function certificateAnimalIds(Certificate $certificate): Collection
    {
        $stored = collect($certificate->animal_intake_item_ids ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0);

        if ($stored->isNotEmpty()) {
            return $stored->values();
        }

        $certificate->loadMissing('batch.items');

        return $certificate->batch?->items
            ->pluck('animal_intake_item_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->values() ?? collect();
    }

    /**
     * @param  Collection<int, int>  $animalIds
     */
    public static function attachStoragesToCertificate(Certificate $certificate, Collection $animalIds): void
    {
        if ($animalIds->isEmpty()) {
            return;
        }

        $certificate->loadMissing('batch');
        $batch = $certificate->batch;
        if ($batch === null) {
            return;
        }

        $releaseByAnimal = BatchAnimalReleaseLookup::forBatch($batch);

        foreach ($animalIds as $animalId) {
            $storage = $releaseByAnimal->get((int) $animalId);
            if ($storage === null) {
                continue;
            }

            $storage->update(['certificate_id' => $certificate->id]);
        }
    }

    /**
     * @param  Collection<int, int>  $animalIds
     * @return Collection<int, WarehouseStorage>
     */
    public static function releasedStoragesForAnimals(Batch $batch, Collection $animalIds): Collection
    {
        $releaseByAnimal = BatchAnimalReleaseLookup::forBatch($batch);

        return $animalIds
            ->map(fn (int $id) => $releaseByAnimal->get($id))
            ->filter(fn (?WarehouseStorage $storage) => $storage !== null && $storage->isReleased())
            ->values();
    }
}
