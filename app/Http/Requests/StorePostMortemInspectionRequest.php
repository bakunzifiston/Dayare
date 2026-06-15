<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesPostMortemItemOutcomes;
use App\Models\Batch;
use App\Support\PostMortemChecklist;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePostMortemInspectionRequest extends FormRequest
{
    use ValidatesPostMortemItemOutcomes;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $batch = Batch::find($this->input('batch_id'));
        if ($batch !== null && $batch->inspectableAnimalsForPostMortem()->isNotEmpty()) {
            $this->merge(['observations' => null]);
        }
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $batchId = (int) $this->input('batch_id');
        $businessId = (int) \App\Models\Batch::query()
            ->join('slaughter_executions', 'slaughter_executions.id', '=', 'batches.slaughter_execution_id')
            ->join('slaughter_plans', 'slaughter_plans.id', '=', 'slaughter_executions.slaughter_plan_id')
            ->join('facilities', 'facilities.id', '=', 'slaughter_plans.facility_id')
            ->where('batches.id', $batchId)
            ->value('facilities.business_id');
        $allowedSpecies = $this->user()?->configuredSpeciesNames([$businessId])->all() ?? [];

        return [
            'batch_id' => ['required', 'exists:batches,id'],
            'inspector_id' => ['required', 'exists:inspectors,id'],
            'species' => ['required', 'string', 'max:50', Rule::in($allowedSpecies)],
            'total_examined' => ['required', 'numeric', 'min:0'],
            'approved_quantity' => ['required', 'numeric', 'min:0'],
            'condemned_quantity' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'inspection_date' => ['required', 'date'],
            'observations' => ['nullable', 'array'],
            'observations.*.value' => ['required_with:observations', 'string', 'max:5000'],
            'observations.*.notes' => ['nullable', 'string', 'max:5000'],
            'item_outcomes' => ['nullable', 'array'],
            'item_outcomes.*.batch_item_id' => ['nullable', 'integer', 'exists:batch_items,id'],
            'item_outcomes.*.animal_intake_item_id' => ['required_with:item_outcomes', 'integer', 'exists:animal_intake_items,id'],
            'item_outcomes.*.outcome' => ['required_with:item_outcomes', 'in:approved,condemned,deferred'],
            'item_outcomes.*.outcome_notes' => ['nullable', 'string', 'max:1000'],
            'item_outcomes.*.carcass_weight_kg' => ['nullable', 'numeric', 'min:0.1', 'max:9999'],
            'item_outcomes.*.observations' => ['nullable', 'array'],
            'item_outcomes.*.observations.*.value' => ['required_with:item_outcomes.*.observations', 'string', 'max:5000'],
            'item_outcomes.*.observations.*.notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $approved = (float) $this->input('approved_quantity');
            $condemned = (float) $this->input('condemned_quantity');
            $examined = (float) $this->input('total_examined');
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
            if (empty(PostMortemChecklist::itemsForSpecies($species))) {
                $validator->errors()->add('species', __('No post-mortem checklist is configured for this species.'));

                return;
            }

            if ($batch) {
                $batchSpecies = (string) ($batch->species ?? '');
                $batchSpeciesKey = PostMortemChecklist::speciesKey($batchSpecies);
                $formSpeciesKey = PostMortemChecklist::speciesKey($species);
                if ($batchSpeciesKey && $formSpeciesKey && $batchSpeciesKey !== $formSpeciesKey) {
                    $validator->errors()->add('species', __('Selected species does not match batch species.'));
                }
            }

            $perAnimal = $batch !== null && $batch->inspectableAnimalsForPostMortem()->isNotEmpty();

            if ($perAnimal) {
                $this->validateItemOutcomesForBatch(
                    $validator,
                    $batch,
                    $species,
                    $this->input('item_outcomes'),
                );

                $maxMeatKg = round((float) $batch->inspectableAnimalsForPostMortem()->sum('meat_quantity_kg'), 2);
                if ($examined > $maxMeatKg + 0.001) {
                    $validator->errors()->add(
                        'total_examined',
                        __('Total meat examined cannot exceed available slaughter meat (:kg kg).', ['kg' => number_format($maxMeatKg, 2)]),
                    );
                }
            } else {
                $this->validateLegacyPostMortemObservations(
                    $validator,
                    $species,
                    $this->input('observations', []),
                );
            }
        });
    }
}
