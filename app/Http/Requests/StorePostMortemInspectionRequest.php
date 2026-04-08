<?php

namespace App\Http\Requests;

use App\Models\Batch;
use App\Support\PostMortemChecklist;
use Illuminate\Foundation\Http\FormRequest;

class StorePostMortemInspectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'batch_id' => ['required', 'exists:batches,id'],
            'inspector_id' => ['required', 'exists:inspectors,id'],
            'species' => ['required', 'string', 'max:50'],
            'total_examined' => ['required', 'integer', 'min:0'],
            'approved_quantity' => ['required', 'integer', 'min:0'],
            'condemned_quantity' => ['required', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'inspection_date' => ['required', 'date'],
            'observations' => ['required', 'array'],
            'observations.*.value' => ['required', 'string', 'max:20'],
            'observations.*.notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $approved = (int) $this->input('approved_quantity');
            $condemned = (int) $this->input('condemned_quantity');
            $examined = (int) $this->input('total_examined');
            if ($approved + $condemned > $examined) {
                $validator->errors()->add(
                    'approved_quantity',
                    __('Approved + Condemned cannot exceed Total Examined.')
                );
            }

            $batch = Batch::with('slaughterExecution.slaughterPlan')->find($this->input('batch_id'));
            $inspectorId = $this->input('inspector_id');
            if ($batch && $inspectorId) {
                $inspector = \App\Models\Inspector::find($inspectorId);
                $facilityId = $batch->slaughterExecution->slaughterPlan->facility_id ?? null;
                if ($inspector && $facilityId !== null && $inspector->facility_id !== $facilityId) {
                    $validator->errors()->add('inspector_id', __('Inspector must be assigned to the batch facility.'));
                }
            }

            $species = (string) $this->input('species');
            $checklistItems = PostMortemChecklist::itemsForSpecies($species);
            if (empty($checklistItems)) {
                $validator->errors()->add('species', __('No post-mortem checklist is configured for this species.'));

                return;
            }

            $observations = $this->input('observations', []);
            foreach ($checklistItems as $itemKey => $meta) {
                $value = $observations[$itemKey]['value'] ?? null;
                if (! is_string($value) || trim($value) === '') {
                    $validator->errors()->add('observations', __('Please complete all post-mortem checklist items.'));

                    continue;
                }

                $allowed = PostMortemChecklist::allowedValuesForItem($species, (string) $itemKey);
                if (! in_array($value, $allowed, true)) {
                    $validator->errors()->add('observations', __('Invalid checklist value for :item.', ['item' => $meta['label'] ?? $itemKey]));
                }
            }

            foreach (array_keys($observations) as $submittedItem) {
                if (! array_key_exists($submittedItem, $checklistItems)) {
                    $validator->errors()->add('observations', __('Unexpected checklist item submitted.'));
                    break;
                }
            }

            if ($batch) {
                $batchSpecies = (string) ($batch->species ?? '');
                $batchSpeciesKey = PostMortemChecklist::speciesKey($batchSpecies);
                $formSpeciesKey = PostMortemChecklist::speciesKey($species);
                if ($batchSpeciesKey && $formSpeciesKey && $batchSpeciesKey !== $formSpeciesKey) {
                    $validator->errors()->add('species', __('Selected species does not match batch species.'));
                }
            }
        });
    }
}
