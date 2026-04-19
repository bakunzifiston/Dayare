<?php

namespace App\Http\Requests\Logistics;

use App\Models\LogisticsDriver;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDriverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_id' => ['required', 'integer', Rule::exists('logistics_companies', 'id')],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'phone_number' => ['required', 'string', 'max:10', 'regex:/^\d{1,10}$/'],
            'national_id_or_license_id' => ['required', 'string', 'max:120'],
            'gender' => ['required', Rule::in(['male', 'female'])],
            'dob' => ['required', 'date', 'before:today'],
            'country_id' => ['nullable', 'integer', Rule::exists('administrative_divisions', 'id')],
            'province_id' => ['nullable', 'integer', Rule::exists('administrative_divisions', 'id')],
            'district_id' => ['nullable', 'integer', Rule::exists('administrative_divisions', 'id')],
            'sector_id' => ['nullable', 'integer', Rule::exists('administrative_divisions', 'id')],
            'cell_id' => ['nullable', 'integer', Rule::exists('administrative_divisions', 'id')],
            'village_id' => ['nullable', 'integer', Rule::exists('administrative_divisions', 'id')],
            'photo' => ['nullable', 'image', 'max:2048'],
            'license_number' => ['required', 'string', 'max:120', 'unique:logistics_drivers,license_number'],
            'license_category' => ['required', 'string', 'max:60'],
            'license_expiry' => ['required', 'date'],
            'experience_years' => ['required', 'integer', 'min:0', 'max:80'],
            'status' => ['nullable', Rule::in(LogisticsDriver::STATUSES)],
        ];
    }

    public function messages(): array
    {
        return [
            'phone_number.regex' => __('Phone number must contain digits only and not exceed 10 digits.'),
        ];
    }
}
