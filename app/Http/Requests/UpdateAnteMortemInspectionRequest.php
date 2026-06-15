<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesAnteMortemItemOutcomes;
use App\Models\SlaughterPlan;
use App\Support\AnteMortemChecklist;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAnteMortemInspectionRequest extends FormRequest
{
    use ValidatesAnteMortemItemOutcomes;
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $planId = (int) $this->input('slaughter_plan_id');
        $businessId = (int) \App\Models\SlaughterPlan::query()
            ->join('facilities', 'facilities.id', '=', 'slaughter_plans.facility_id')
            ->where('slaughter_plans.id', $planId)
            ->value('facilities.business_id');
        $allowedSpecies = $this->user()?->configuredSpeciesNames([$businessId])->all() ?? [];

        return [
            'slaughter_plan_id' => ['sometimes', 'required', 'exists:slaughter_plans,id'],
            'inspector_id' => ['sometimes', 'required', 'exists:inspectors,id'],
            'species' => ['required', 'string', 'max:50', Rule::in($allowedSpecies)],
            'number_examined' => ['required', 'integer', 'min:0'],
            'number_approved' => ['required', 'integer', 'min:0'],
            'number_rejected' => ['required', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:5000'],
            // --- Section 3 ---
            'notes_for_under_observation' => ['nullable', 'string', 'max:2000'],
            'item_outcomes' => ['nullable', 'array'],
            'item_outcomes.*.animal_intake_item_id' => ['required_with:item_outcomes', 'integer', 'exists:animal_intake_items,id'],
            'item_outcomes.*.outcome' => ['required_with:item_outcomes', 'in:approved,rejected,deferred'],
            'item_outcomes.*.outcome_notes' => ['nullable', 'string', 'max:1000'],
            'item_outcomes.*.observations' => ['nullable', 'array'],
            'item_outcomes.*.observations.*.value' => ['nullable', 'string', 'max:5000'],
            'item_outcomes.*.observations.*.notes' => ['nullable', 'string', 'max:5000'],
            'inspection_date' => ['required', 'date'],
            'observations' => ['nullable', 'array'],
            'observations.*.value' => ['nullable', 'string', 'max:5000'],
            'observations.*.notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $approved = (int) $this->input('number_approved');
            $rejected = (int) $this->input('number_rejected');
            $examined = (int) $this->input('number_examined');
            if ($approved + $rejected > $examined) {
                $validator->errors()->add(
                    'number_approved',
                    __('Approved + Rejected cannot exceed Number Examined.')
                );
            }

            $plan = SlaughterPlan::find($this->input('slaughter_plan_id'));
            $inspectorId = $this->input('inspector_id');
            if ($plan && $inspectorId) {
                $inspector = \App\Models\Inspector::find($inspectorId);
                if ($inspector && $inspector->facility_id !== $plan->facility_id) {
                    $validator->errors()->add('inspector_id', __('Inspector must be assigned to the slaughter session facility.'));
                }
            }

            // --- Section 3 ---
            if ($plan && $plan->species !== $this->input('species')) {
                $validator->errors()->add(
                    'species',
                    __('Inspection species must match the slaughter plan species.'),
                );
            }

            if ($plan && $plan->assignedItems()->exists()) {
                $assignedCount = $plan->assignedItems()
                    ->where('species', $this->input('species'))
                    ->count();
                $examined = (int) $this->input('number_examined');
                if ($examined > $assignedCount) {
                    $validator->errors()->add(
                        'number_examined',
                        __('Only :count :species animals are assigned to this plan — cannot examine more than :count.', [
                            'count' => $assignedCount,
                            'species' => $this->input('species'),
                        ]),
                    );
                }
            }

            $species = (string) $this->input('species');
            $hasAssignedAnimals = $plan !== null
                && $plan->assignedItems()->where('species', $species)->exists();

            if ($hasAssignedAnimals) {
                $this->validateItemOutcomesForPlan(
                    $validator,
                    $plan,
                    $species,
                    $this->input('item_outcomes'),
                );
            } else {
                $this->validateLegacyObservations(
                    $validator,
                    $species,
                    is_array($this->input('observations')) ? $this->input('observations') : [],
                );
            }
        });
    }
}
