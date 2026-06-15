<?php

namespace App\Http\Requests\Concerns;

use App\Models\Batch;
use App\Support\PostMortemChecklist;
use Illuminate\Contracts\Validation\Validator;

trait ValidatesPostMortemItemOutcomes
{
    protected function validateItemOutcomesForBatch(
        Validator $validator,
        ?Batch $batch,
        string $species,
        mixed $itemOutcomes,
    ): void {
        if ($batch === null) {
            return;
        }

        $animalIds = $batch->inspectableAnimalsForPostMortem()
            ->pluck('animal_intake_item_id')
            ->map(fn ($id) => (int) $id)
            ->sort()
            ->values();

        if ($animalIds->isEmpty()) {
            return;
        }

        if (! is_array($itemOutcomes) || $itemOutcomes === []) {
            $validator->errors()->add(
                'item_outcomes',
                __('Each animal must have a post-mortem outcome.'),
            );

            return;
        }

        $submittedIds = collect($itemOutcomes)
            ->pluck('animal_intake_item_id')
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($submittedIds->count() !== $animalIds->count()) {
            $validator->errors()->add(
                'item_outcomes',
                __('Outcomes are required for all :count animals.', ['count' => $animalIds->count()]),
            );

            return;
        }

        if ($submittedIds->duplicates()->isNotEmpty()) {
            $validator->errors()->add(
                'item_outcomes',
                __('Each animal may only appear once in the outcomes list.'),
            );

            return;
        }

        if ($submittedIds->diff($animalIds)->isNotEmpty()) {
            $validator->errors()->add(
                'item_outcomes',
                __('One or more animals are not available for the selected batch.'),
            );

            return;
        }

        $validBatchItemIds = $batch->items()->pluck('id')->map(fn ($id) => (int) $id)->all();

        foreach ($itemOutcomes as $index => $outcome) {
            $batchItemId = isset($outcome['batch_item_id']) ? (int) $outcome['batch_item_id'] : null;
            if ($batchItemId !== null && ! in_array($batchItemId, $validBatchItemIds, true)) {
                $validator->errors()->add(
                    "item_outcomes.{$index}.batch_item_id",
                    __('This animal does not belong to the selected batch.'),
                );
            }
        }

        $this->validatePerAnimalPostMortemObservations($validator, $species, $itemOutcomes);
    }

    /**
     * @param  array<int, array<string, mixed>>|null  $itemOutcomes
     */
    protected function validatePerAnimalPostMortemObservations(
        Validator $validator,
        string $species,
        mixed $itemOutcomes,
    ): void {
        if (! is_array($itemOutcomes) || $itemOutcomes === []) {
            return;
        }

        $checklistItems = PostMortemChecklist::itemsForInspection($species, true);

        foreach ($itemOutcomes as $index => $outcome) {
            $observations = is_array($outcome['observations'] ?? null) ? $outcome['observations'] : [];

            foreach ($checklistItems as $itemKey => $meta) {
                $value = $observations[$itemKey]['value'] ?? null;
                $isFreeText = ($meta['type'] ?? null) === 'free_text';

                if ($isFreeText) {
                    $value = is_string($value) ? $value : '';
                } elseif (! is_string($value) || trim($value) === '') {
                    $validator->errors()->add(
                        "item_outcomes.{$index}.observations",
                        __('Please complete all checklist items for each animal.'),
                    );

                    continue;
                }

                $allowed = PostMortemChecklist::allowedValuesForItem($species, (string) $itemKey);
                if (! empty($allowed) && ! in_array($value, $allowed, true)) {
                    $validator->errors()->add(
                        "item_outcomes.{$index}.observations",
                        __('Invalid checklist value for :item.', ['item' => $meta['label'] ?? $itemKey]),
                    );
                }
            }

            foreach (array_keys($observations) as $submittedItem) {
                if (! array_key_exists($submittedItem, $checklistItems)) {
                    $validator->errors()->add(
                        "item_outcomes.{$index}.observations",
                        __('Unexpected checklist item submitted.'),
                    );
                    break;
                }
            }
        }
    }

    /**
     * @param  array<string, array{value?: string|null, notes?: string|null}>  $observations
     */
    protected function validateLegacyPostMortemObservations(
        Validator $validator,
        string $species,
        array $observations,
    ): void {
        $checklistItems = PostMortemChecklist::itemsForSpecies($species);

        foreach ($checklistItems as $itemKey => $meta) {
            $value = $observations[$itemKey]['value'] ?? null;
            $isFreeText = ($meta['type'] ?? null) === 'free_text';

            if ($isFreeText) {
                $value = is_string($value) ? $value : '';
            } elseif (! is_string($value) || trim($value) === '') {
                $validator->errors()->add('observations', __('Please complete all post-mortem checklist items.'));

                continue;
            }

            $allowed = PostMortemChecklist::allowedValuesForItem($species, (string) $itemKey);
            if (! empty($allowed) && ! in_array($value, $allowed, true)) {
                $validator->errors()->add('observations', __('Invalid checklist value for :item.', ['item' => $meta['label'] ?? $itemKey]));
            }
        }

        if ($checklistItems !== []) {
            foreach (array_keys($observations) as $submittedItem) {
                if (! array_key_exists($submittedItem, $checklistItems)) {
                    $validator->errors()->add('observations', __('Unexpected checklist item submitted.'));
                    break;
                }
            }
        }
    }
}
