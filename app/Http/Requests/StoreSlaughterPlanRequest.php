<?php

namespace App\Http\Requests;

use App\Models\SlaughterPlan;
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
        return [
            'slaughter_date' => ['required', 'date', 'after_or_equal:today'],
            'facility_id' => ['required', 'exists:facilities,id'],
            'inspector_id' => [
                'required',
                'exists:inspectors,id',
                Rule::exists('inspectors', 'id')->where('facility_id', $this->input('facility_id')),
            ],
            'species' => ['required', 'string', 'max:50', Rule::in(SlaughterPlan::SPECIES_OPTIONS)],
            'number_of_animals_scheduled' => ['required', 'integer', 'min:1'],
            'status' => ['required', 'string', Rule::in(SlaughterPlan::STATUSES)],
        ];
    }
}
