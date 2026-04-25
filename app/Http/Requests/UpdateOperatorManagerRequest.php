<?php

namespace App\Http\Requests;

use App\Models\OperatorManager;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOperatorManagerRequest extends FormRequest
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
        $operatorManagerId = $this->route('operator_manager') instanceof OperatorManager 
            ? $this->route('operator_manager')->id 
            : $this->route('operator_manager');

        return [
            'facility_id' => ['sometimes', 'required', 'exists:facilities,id'],
            'first_name' => ['sometimes', 'required', 'string', 'max:100'],
            'last_name' => ['sometimes', 'required', 'string', 'max:100'],
            'national_id' => [
                'sometimes', 
                'required', 
                'string', 
                'max:50', 
                Rule::unique('operator_managers', 'national_id')->ignore($operatorManagerId)
            ],
            'phone_number' => ['sometimes', 'required', 'string', 'max:50'],
            'email' => ['sometimes', 'required', 'email', 'max:255'],
            'dob' => ['nullable', 'date', 'before:today'],
            'nationality' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'country_id' => ['nullable'],
            'province_id' => ['nullable'],
            'district_id' => ['nullable'],
            'sector_id' => ['nullable'],
            'cell_id' => ['nullable'],
            'village_id' => ['nullable'],
            'district' => ['nullable', 'string', 'max:100'],
            'sector' => ['nullable', 'string', 'max:100'],
            'cell' => ['nullable', 'string', 'max:100'],
            'village' => ['nullable', 'string', 'max:100'],
            'status' => ['sometimes', 'required', 'string', Rule::in(OperatorManager::STATUSES)],
        ];
    }
}
