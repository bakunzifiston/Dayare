<?php

namespace App\Http\Requests\Concerns;

use App\Models\Batch;
use App\Models\PostMortemInspectionItem;
use App\Support\PostMortemChecklist;
use Illuminate\Contracts\Validation\Validator;

trait ValidatesPostMortemItemOutcomes
{
    protected function validateItemOutcomesForBatch(
        Validator $validator,
        ?Batch $batch,
        string $species,
        mixed $itemOutcomes,
        ?int $inspectionId = null,
        bool $requireAllAnimals = false,
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
                __('Select at least one animal from the slaughter execution.'),
            );

            return;
        }

        $submittedIds = collect($itemOutcomes)
            ->pluck('animal_intake_item_id')
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($requireAllAnimals && $submittedIds->count() !== $animalIds->count()) {
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
                __('One or more animals are not available for the selected slaughter execution.'),
            );

            return;
        }

        $alreadyInspectedIds = $batch->slaughterExecution
            ? $batch->slaughterExecution->inspectedAnimalIntakeItemIds()
            : collect();

        if ($inspectionId !== null) {
            $alreadyInspectedIds = $alreadyInspectedIds->diff(
                PostMortemInspectionItem::query()
                    ->where('post_mortem_inspection_id', $inspectionId)
                    ->pluck('animal_intake_item_id')
                    ->map(fn ($id) => (int) $id),
            );
        }

        $duplicateInspection = $submittedIds->intersect($alreadyInspectedIds);
        if ($duplicateInspection->isNotEmpty()) {
            $validator->errors()->add(
                'item_outcomes',
                __('One or more animals already have a post-mortem outcome recorded.'),
            );

            return;
        }

        $validBatchItemIds = $batch->items()->pluck('id')->map(fn ($id) => (int) $id)->all();

        foreach ($itemOutcomes as $index => $outcome) {
            $batchItemId = isset($outcome['batch_item_id']) ? (int) $outcome['batch_item_id'] : null;
            if ($batchItemId !== null && ! in_array($batchItemId, $validBatchItemIds, true)) {
                $validator->errors()->add(
                    "item_outcomes.{$index}.batch_item_id",
                    __('This animal does not belong to the selected slaughter execution.'),
                );
            }
        }

        $this->validatePerAnimalPostMortemObservations($validator, $species, $itemOutcomes);
        $this->validateCondemnedPostMortemDetails($validator, $species, $itemOutcomes);
    }

    /**
     * @param  array<int, array<string, mixed>>|null  $itemOutcomes
     */
    protected function validateCondemnedPostMortemDetails(
        Validator $validator,
        string $species,
        mixed $itemOutcomes,
    ): void {
        if (! is_array($itemOutcomes) || $itemOutcomes === []) {
            return;
        }

        foreach ($itemOutcomes as $index => $outcome) {
            if (! is_array($outcome)) {
                continue;
            }

            $decision = (string) ($outcome['outcome'] ?? '');
            $reason = trim((string) ($outcome['reason'] ?? ''));
            $organs = \App\Support\PostMortemCondemnedOrgans::normalizeFromOutcome($outcome);
            $hasAnyOrganDetail = $organs !== [];

            if ($decision === PostMortemInspectionItem::OUTCOME_CONDEMNED) {
                if ($organs === []) {
                    $validator->errors()->add(
                        "item_outcomes.{$index}.condemned_organs",
                        __('Add at least one condemned organ when the decision is condemned.'),
                    );
                } elseif (! \App\Support\PostMortemCondemnedOrgans::hasCompleteRows($organs)) {
                    $validator->errors()->add(
                        "item_outcomes.{$index}.condemned_organs",
                        __('Each condemned organ needs a name and a weight greater than zero.'),
                    );
                }

                if ($reason === '') {
                    $validator->errors()->add(
                        "item_outcomes.{$index}.reason",
                        __('Reason for condemnation is required when the decision is condemned.'),
                    );
                }

                continue;
            }

            if ($decision === PostMortemInspectionItem::OUTCOME_APPROVED && $hasAnyOrganDetail) {
                if (! \App\Support\PostMortemCondemnedOrgans::hasCompleteRows($organs)) {
                    $validator->errors()->add(
                        "item_outcomes.{$index}.condemned_organs",
                        __('Each condemned organ needs a name and a weight greater than zero.'),
                    );
                }

                if ($reason === '') {
                    $validator->errors()->add(
                        "item_outcomes.{$index}.reason",
                        __('Reason for condemnation is required when recording partial condemnation.'),
                    );
                }
            }
        }
    }

    /**
     * @param  array<string, array{value?: string|null, notes?: string|null}>  $observations
     */
    protected function validateLegacyCondemnationDetails(Validator $validator, array $observations): void
    {
        if (($observations['decision']['value'] ?? '') !== 'rejected') {
            return;
        }

        $organ = trim((string) ($observations['condemned_organ']['value'] ?? ''));
        $weight = $observations['condemned_weight_kg']['value'] ?? null;

        if ($organ === '') {
            $validator->errors()->add(
                'observations.condemned_organ.value',
                __('Condemned organ is required when the decision is rejected.'),
            );
        }

        if ($weight === null || $weight === '' || (float) $weight <= 0) {
            $validator->errors()->add(
                'observations.condemned_weight_kg.value',
                __('Condemned weight (kg) is required when the decision is rejected.'),
            );
        }
    }

    /**
     * @param  array<string, array{value?: string|null, notes?: string|null}>  $observations
     */
    protected function postMortemHasAbnormalOrgan(string $species, array $observations): bool
    {
        $checklistItems = PostMortemChecklist::itemsForInspection($species, true);

        foreach ($observations as $itemKey => $row) {
            $meta = $checklistItems[$itemKey] ?? null;

            if (($meta['category'] ?? '') !== 'organ') {
                continue;
            }

            if (PostMortemChecklist::isAbnormalValue((string) ($row['value'] ?? ''))) {
                return true;
            }
        }

        return false;
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
