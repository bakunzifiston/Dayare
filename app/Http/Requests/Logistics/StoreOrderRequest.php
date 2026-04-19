<?php

namespace App\Http\Requests\Logistics;

use App\Models\LogisticsOrder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_id' => ['required', 'integer', Rule::exists('logistics_companies', 'id')],
            'service_type' => ['required', Rule::in(LogisticsOrder::SERVICE_TYPES)],
            'transport_mode' => ['required', Rule::in(LogisticsOrder::TRANSPORT_MODES)],
            'pickup_location' => ['required', 'string', 'max:255'],
            'delivery_location' => ['required', 'string', 'max:255'],
            'total_weight' => ['required', 'numeric', 'min:0'],
            'total_volume' => ['required', 'numeric', 'min:0'],
            'special_instructions' => ['nullable', 'string', 'max:5000'],
            'status' => ['nullable', Rule::in(LogisticsOrder::STATUSES)],
        ];
    }
}
