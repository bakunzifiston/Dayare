<?php

namespace App\Http\Requests\Farmer\Concerns;

use App\Models\Livestock;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

trait ValidatesLivestockGroup
{
    /** @return array<string, mixed> */
    protected function livestockGroupRules(): array
    {
        return [
            'livestock_name' => ['required', 'string', 'max:255'],
            'livestock_type' => ['required', 'string', 'max:64'],
            'production_purpose' => ['required', 'string', 'max:64'],
            'total_count' => ['required', 'integer', 'min:0'],
            'male_count' => ['required', 'integer', 'min:0'],
            'female_count' => ['required', 'integer', 'min:0'],
            'young_count' => ['required', 'integer', 'min:0'],
            'farming_method' => ['required', 'string', 'max:64'],
            'feeding_method' => ['required', 'string', 'max:64', Rule::in(Livestock::FEEDING_TYPES)],
            'water_source' => ['required', 'string', 'max:64'],
            'acquisition_date' => ['required', 'date', 'before_or_equal:today'],
            'acquisition_source' => ['required', 'string', 'max:120'],
            'health_status' => ['required', 'string', Rule::in(Livestock::HEALTH_STATUSES)],
            'lifecycle_status' => ['required', 'string', Rule::in(Livestock::LIFECYCLE_STATUSES)],
            'housing_location' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', 'string', Rule::in(Livestock::STATUSES)],
            'quality_band' => ['nullable', 'string', Rule::in(Livestock::QUALITY_BANDS)],
            'base_price' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $male = (int) $this->input('male_count', 0);
            $female = (int) $this->input('female_count', 0);
            $young = (int) $this->input('young_count', 0);
            $total = (int) $this->input('total_count', 0);

            if ($male + $female + $young > $total) {
                $validator->errors()->add('total_count', __('Total count must be greater than or equal to male, female, and young counts combined.'));
            }
        });
    }
}
