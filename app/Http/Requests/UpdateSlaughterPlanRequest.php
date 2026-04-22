<?php

namespace App\Http\Requests;

use App\Models\AnimalIntake;
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

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $intakeId = $this->input('animal_intake_id');
            if (! $intakeId) {
                return; // nullable for backward compatibility on existing plans
            }
            $intake = AnimalIntake::find($intakeId);
            if (! $intake) {
                return;
            }
            if ($intake->facility_id != $this->input('facility_id')) {
                $validator->errors()->add('animal_intake_id', __('Selected intake must be for the chosen facility.'));
            }
            if ($intake->isHealthCertificateExpired()) {
                $validator->errors()->add('animal_intake_id', __('Cannot schedule slaughter: health certificate has expired.'));
            }
            if ($intake->species !== $this->input('species')) {
                $validator->errors()->add('species', __('Species must match the animal intake.'));
            }
            $plan = $this->route('slaughter_plan') ?? $this->route('slaughterPlan');
            $otherScheduled = $plan && $plan->animal_intake_id == $intakeId
                ? (int) $intake->slaughterPlans()->where('id', '!=', $plan->id)->sum('number_of_animals_scheduled')
                : (int) $intake->totalScheduledForSlaughter();
            $remaining = $intake->number_of_animals - $otherScheduled;
            $scheduled = (int) $this->input('number_of_animals_scheduled');
            if ($scheduled > max(0, $remaining)) {
                $validator->errors()->add('number_of_animals_scheduled', __('Number scheduled cannot exceed animals received from this intake.'));
            }
        });
    }
}
