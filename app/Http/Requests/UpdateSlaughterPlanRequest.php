<?php

namespace App\Http\Requests;

use App\Models\AnimalIntake;
use App\Models\AnimalIntakeItem;
use App\Services\Processor\SlaughterPlanAssignmentService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSlaughterPlanRequest extends FormRequest
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
        $facilityId = (int) $this->input('facility_id');
        $businessId = (int) \App\Models\Facility::query()->whereKey($facilityId)->value('business_id');
        $allowedSpecies = $this->user()?->configuredSpeciesNames([$businessId])->all() ?? [];

        return [
            'slaughter_date' => ['required', 'date'],
            'facility_id' => ['required', 'exists:facilities,id'],
            'animal_intake_id' => ['nullable', 'exists:animal_intakes,id'],
            'inspector_id' => [
                'required',
                'exists:inspectors,id',
                Rule::exists('inspectors', 'id')->where('facility_id', $this->input('facility_id')),
            ],
            'species' => ['required', 'string', 'max:50', Rule::in($allowedSpecies)],
            'number_of_animals_scheduled' => ['required', 'integer', 'min:1'],
            'status' => ['required', 'string', Rule::in(\App\Models\SlaughterPlan::STATUSES)],
        ];
    }

    // --- Section B: item-aware intake validation ---

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $intakeId = $this->input('animal_intake_id');
            if (! $intakeId) {
                return;
            }
            $intake = AnimalIntake::find($intakeId);
            if (! $intake) {
                return;
            }

            $plan = $this->route('slaughter_plan') ?? $this->route('slaughterPlan');
            $assignmentService = app(SlaughterPlanAssignmentService::class);
            $requestedSpecies = (string) $this->input('species');
            $scheduled = (int) $this->input('number_of_animals_scheduled');
            $newIntakeId = (int) $intakeId;
            $intakeChanged = ! $plan || $newIntakeId !== (int) $plan->animal_intake_id;

            if ($intake->facility_id != $this->input('facility_id')) {
                $validator->errors()->add('animal_intake_id', __('Selected intake must be for the chosen facility.'));
            }

            if ($intakeChanged && ! $intake->isPlannableForSlaughter()) {
                $validator->errors()->add(
                    'animal_intake_id',
                    __('The selected intake must be submitted before a slaughter plan can be created.'),
                );

                return;
            }

            if ($intake->items()->exists()) {
                $availableForSpecies = $assignmentService->availableCountForSpecies($intake, $requestedSpecies);
                $assignedToThisPlan = $plan
                    ? (int) AnimalIntakeItem::query()
                        ->where('slaughter_plan_id', $plan->id)
                        ->where('species', $requestedSpecies)
                        ->count()
                    : 0;

                if ($availableForSpecies === 0 && $assignedToThisPlan === 0) {
                    $validator->errors()->add(
                        'species',
                        __('No available :species animals on this intake.', ['species' => $requestedSpecies]),
                    );
                }

                $available = $availableForSpecies + $assignedToThisPlan;
            } else {
                if ($intake->species !== $requestedSpecies) {
                    $validator->errors()->add('species', __('Species must match the animal intake.'));
                }

                $otherScheduled = $plan && $plan->animal_intake_id == $intakeId
                    ? (int) $intake->slaughterPlans()->where('id', '!=', $plan->id)->sum('number_of_animals_scheduled')
                    : (int) $intake->totalScheduledForSlaughter();
                $available = max(0, $intake->number_of_animals - $otherScheduled);
            }

            if ($scheduled > $available) {
                $validator->errors()->add(
                    'number_of_animals_scheduled',
                    __('Only :available :species animals are available — :scheduled requested.', [
                        'available' => $available,
                        'species' => $requestedSpecies,
                        'scheduled' => $scheduled,
                    ]),
                );
            }
        });
    }
}
