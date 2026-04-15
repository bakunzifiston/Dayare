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
            'client_id' => ['required', 'integer', Rule::exists('businesses', 'id')],
            'pickup_location' => ['required', 'string', 'max:255'],
            'delivery_location' => ['required', 'string', 'max:255'],
            'species' => ['nullable', 'string', 'max:120'],
            'quantity' => ['required', 'integer', 'min:1'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'requested_date' => ['required', 'date'],
            'priority' => ['required', Rule::in(LogisticsOrder::PRIORITIES)],
            'status' => ['nullable', Rule::in(LogisticsOrder::STATUSES)],
        ];
    }
}

