<?php

namespace App\Http\Requests\Concerns;

use App\Models\AnteMortemInspectionItem;
use App\Models\SlaughterPlan;
use App\Support\AnteMortemChecklist;
use Illuminate\Contracts\Validation\Validator;

trait ValidatesAnteMortemItemOutcomes
{
    protected function validateItemOutcomesForPlan(
        Validator $validator,
        ?SlaughterPlan $plan,
        string $species,
        mixed $itemOutcomes,
    ): void {
        if ($plan === null) {
            return;
        }

        $assignedIds = $plan->assignedItems()
            ->where('species', $species)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->sort()
            ->values();

        if ($assignedIds->isEmpty()) {
            return;
        }

        if (! is_array($itemOutcomes) || $itemOutcomes === []) {
            $validator->errors()->add(
                'item_outcomes',
                __('Each assigned animal must have an inspection outcome.'),
            );

            return;
        }

        $submittedIds = collect($itemOutcomes)
            ->pluck('animal_intake_item_id')
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($submittedIds->count() !== $assignedIds->count()) {
            $validator->errors()->add(
                'item_outcomes',
                __('Outcomes are required for all :count assigned animals.', ['count' => $assignedIds->count()]),
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

        if ($submittedIds->diff($assignedIds)->isNotEmpty()) {
            $validator->errors()->add(
                'item_outcomes',
                __('One or more animals are not assigned to this slaughter plan.'),
            );

            return;
        }

        $this->validatePerAnimalObservations($validator, $species, $itemOutcomes);
        $this->validateUnhealthyAnteMortemDetails($validator, $species, $itemOutcomes);
    }

    /**
     * @param  array<int, array<string, mixed>>|null  $itemOutcomes
     */
    protected function validateUnhealthyAnteMortemDetails(
        Validator $validator,
        string $species,
        mixed $itemOutcomes,
    ): void {
        if (! is_array($itemOutcomes) || $itemOutcomes === []) {
            return;
        }

        foreach ($itemOutcomes as $index => $outcome) {
            if (! in_array($outcome['outcome'] ?? '', [
                AnteMortemInspectionItem::OUTCOME_REJECTED,
                AnteMortemInspectionItem::OUTCOME_DEFERRED,
            ], true)) {
                continue;
            }

            $observations = is_array($outcome['observations'] ?? null) ? $outcome['observations'] : [];
            $conditions = trim((string) ($outcome['conditions'] ?? ''));
            $actionTaken = trim((string) ($outcome['action_taken'] ?? ''));
            $outcomeNotes = trim((string) ($outcome['outcome_notes'] ?? ''));

            if ($conditions === '' && ! $this->anteMortemHasUnhealthyFinding($species, $observations)) {
                $validator->errors()->add(
                    "item_outcomes.{$index}.conditions",
                    __('Provide condition(s) or mark an abnormal finding for rejected/deferred animals.'),
                );
            }

            if ($actionTaken === '' && $outcomeNotes === '') {
                $validator->errors()->add(
                    "item_outcomes.{$index}.action_taken",
                    __('Action taken is required for rejected/deferred animals.'),
                );
            }
        }
    }

    /**
     * @param  array<string, array{value?: string|null, notes?: string|null}>  $observations
     */
    protected function anteMortemHasUnhealthyFinding(string $species, array $observations): bool
    {
        $checklistItems = AnteMortemChecklist::itemsForInspection($species, true);

        foreach ($observations as $itemKey => $row) {
            $value = trim((string) ($row['value'] ?? ''));

            if ($itemKey === 'observation' && $value !== '') {
                return true;
            }

            if (! array_key_exists($itemKey, $checklistItems)) {
                continue;
            }

            if (in_array($value, ['abnormal', 'no'], true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, array<string, mixed>>|null  $itemOutcomes
     */
    protected function validatePerAnimalObservations(
        Validator $validator,
        string $species,
        mixed $itemOutcomes,
    ): void {
        if (! is_array($itemOutcomes) || $itemOutcomes === []) {
            return;
        }

        $checklistItems = AnteMortemChecklist::itemsForInspection($species, true);

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

                $allowed = AnteMortemChecklist::allowedValuesForItem($species, (string) $itemKey);
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
    protected function validateLegacyObservations(
        Validator $validator,
        string $species,
        array $observations,
    ): void {
        $checklistItems = AnteMortemChecklist::itemsForInspection($species, false);

        foreach ($checklistItems as $itemKey => $meta) {
            $value = $observations[$itemKey]['value'] ?? null;
            $isFreeText = ($meta['type'] ?? null) === 'free_text';

            if ($isFreeText) {
                $value = is_string($value) ? $value : '';
            } elseif (! is_string($value) || trim($value) === '') {
                $validator->errors()->add('observations', __('Please complete all species checklist items.'));

                continue;
            }

            $allowed = AnteMortemChecklist::allowedValuesForItem($species, (string) $itemKey);
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
