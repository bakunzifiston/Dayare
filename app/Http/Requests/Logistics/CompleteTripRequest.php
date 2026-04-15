<?php

namespace App\Http\Requests\Logistics;

use App\Models\LogisticsTrip;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompleteTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'actual_arrival' => ['nullable', 'date'],
            'status' => ['required', Rule::in([LogisticsTrip::STATUS_DELIVERED, LogisticsTrip::STATUS_FAILED])],
            'deliveries' => ['nullable', 'array', 'required_if:status,'.LogisticsTrip::STATUS_DELIVERED, 'min:1'],
            'deliveries.*.order_id' => ['required_with:deliveries', 'integer', Rule::exists('logistics_orders', 'id')],
            'deliveries.*.delivered_quantity' => ['required_with:deliveries', 'integer', 'min:0'],
            'deliveries.*.loss_quantity' => ['nullable', 'integer', 'min:0'],
        ];
    }
}

