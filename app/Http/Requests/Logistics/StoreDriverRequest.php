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
            'name' => ['required', 'string', 'max:150'],
            'license_number' => ['required', 'string', 'max:120', 'unique:logistics_drivers,license_number'],
            'license_expiry' => ['required', 'date'],
            'status' => ['nullable', Rule::in(LogisticsDriver::STATUSES)],
        ];
    }
}

