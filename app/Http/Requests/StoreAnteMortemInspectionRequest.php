<?php

namespace App\Http\Requests;

use App\Models\SlaughterPlan;
use App\Support\AnteMortemChecklist;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAnteMortemInspectionRequest extends FormRequest
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
        $planId = (int) $this->input('slaughter_plan_id');
        $businessId = (int) \App\Models\SlaughterPlan::query()
            ->join('facilities', 'facilities.id', '=', 'slaughter_plans.facility_id')
            ->where('slaughter_plans.id', $planId)
            ->value('facilities.business_id');
        $allowedSpecies = $this->user()?->configuredSpeciesNames([$businessId])->all() ?? [];

        return [
            'slaughter_plan_id' => ['required', 'exists:slaughter_plans,id'],
            'inspector_id' => ['required', 'exists:inspectors,id'],
            'species' => ['required', 'string', 'max:50'],
            'number_examined' => ['required', 'integer', 'min:0'],
            'number_approved' => ['required', 'integer', 'min:0'],
            'number_rejected' => ['required', 'integer', 'min:0'],
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

            $species = (string) $this->input('species');
            $checklistItems = AnteMortemChecklist::itemsForSpecies($species);
            $observations = $this->input('observations', []);

            foreach ($checklistItems as $itemKey => $meta) {
                $value = $observations[$itemKey]['value'] ?? null;
                if (!is_string($value) || trim($value) === '') {
                    $validator->errors()->add('observations', __('Please complete all species checklist items.'));

                    continue;
                }

                $allowed = AnteMortemChecklist::allowedValuesForItem($species, (string) $itemKey);
                if (!in_array($value, $allowed, true)) {
                    $validator->errors()->add('observations', __('Invalid checklist value for :item.', ['item' => $meta['label'] ?? $itemKey]));
                }
            }

            if (!empty($checklistItems)) {
                foreach (array_keys($observations) as $submittedItem) {
                    if (!array_key_exists($submittedItem, $checklistItems)) {
                        $validator->errors()->add('observations', __('Unexpected checklist item submitted.'));
                        break;
                    }
                }
            }
        });
    }
}
