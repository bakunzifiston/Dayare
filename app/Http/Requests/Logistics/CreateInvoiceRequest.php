<?php

namespace App\Http\Requests\Logistics;

use App\Models\LogisticsInvoice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'base_cost' => ['required', 'numeric', 'min:0'],
            'cost_per_km' => ['required', 'numeric', 'min:0'],
            'distance_km' => ['nullable', 'numeric', 'min:0'],
            'cost_per_unit' => ['required', 'numeric', 'min:0'],
            'extra_charges' => ['nullable', 'numeric', 'min:0'],
            'payment_status' => ['nullable', Rule::in(LogisticsInvoice::PAYMENT_STATUSES)],
        ];
    }
}

