<?php

namespace App\Http\Requests;

use App\Models\OperatorManager;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOperatorManagerRequest extends FormRequest
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
            'facility_id' => ['required', 'exists:facilities,id'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'national_id' => ['required', 'string', 'max:50', 'unique:operator_managers,national_id'],
            'phone_number' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255'],
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
            'status' => ['required', 'string', Rule::in(OperatorManager::STATUSES)],
            'password' => ['nullable', 'string', 'min:8'],
        ];
    }
}
