<?php

namespace App\Http\Requests;

use App\Models\AnimalIntake;
use App\Models\SlaughterPlan;
use App\Services\Processor\SlaughterPlanAssignmentService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSlaughterPlanRequest extends FormRequest
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
            'slaughter_date' => ['required', 'date', 'after_or_equal:today'],
            'facility_id' => ['required', 'exists:facilities,id'],
            'animal_intake_id' => ['required', 'exists:animal_intakes,id'],
            'inspector_id' => [
                'required',
                'exists:inspectors,id',
                Rule::exists('inspectors', 'id')->where('facility_id', $this->input('facility_id')),
            ],
            'species' => ['required', 'string', 'max:50', Rule::in($allowedSpecies)],
            'number_of_animals_scheduled' => ['required', 'integer', 'min:1'],
            'status' => ['required', 'string', Rule::in(SlaughterPlan::STATUSES)],
        ];
    }

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

            $assignmentService = app(SlaughterPlanAssignmentService::class);
            $requestedSpecies = (string) $this->input('species');
            $scheduled = (int) $this->input('number_of_animals_scheduled');

            if ($intake->facility_id != $this->input('facility_id')) {
                $validator->errors()->add('animal_intake_id', __('Selected intake must be for the chosen facility.'));
            }

            if ($intake->isHealthCertificateExpired()) {
                $validator->errors()->add('animal_intake_id', __('Cannot schedule slaughter: health certificate has expired.'));
            }

            if (! $intake->isPlannableForSlaughter()) {
                $validator->errors()->add(
                    'animal_intake_id',
                    __('The selected intake must be submitted before a slaughter plan can be created.'),
                );

                return;
            }

            if ($intake->items()->exists()) {
                $availableForSpecies = $assignmentService->availableCountForSpecies($intake, $requestedSpecies);
                if ($availableForSpecies === 0) {
                    $validator->errors()->add(
                        'species',
                        __('No available :species animals on this intake.', ['species' => $requestedSpecies]),
                    );
                }

                $remaining = $availableForSpecies;
            } else {
                if ($intake->species !== $requestedSpecies) {
                    $validator->errors()->add('species', __('Species must match the animal intake.'));
                }

                $remaining = $intake->remainingAnimalsAvailable();
            }

            if ($scheduled > $remaining) {
                $validator->errors()->add(
                    'number_of_animals_scheduled',
                    __('Only :remaining :species animals are available — :scheduled requested.', [
                        'remaining' => $remaining,
                        'species' => $requestedSpecies,
                        'scheduled' => $scheduled,
                    ]),
                );
            }
        });
    }
}
