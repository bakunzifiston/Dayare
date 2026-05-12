<?php

namespace App\Http\Requests\Farmer\Concerns;

use App\Models\Buyer;
use App\Models\Sale;
use App\Models\SaleAnimal;
use Illuminate\Validation\Rule;

trait ValidatesSaleRegistration
{
    /** @return array<string, mixed> */
    protected function saleRegistrationRules(): array
    {
        return [
            'farm_id' => ['required', 'integer', 'exists:farms,id'],
            'buyer_id' => ['required', 'integer', 'exists:buyers,id'],
            'sale_type' => ['required', 'string', Rule::in(Sale::TYPES)],
            'sale_date' => ['required', 'date', 'before_or_equal:today'],
            'sale_status' => ['required', 'string', Rule::in(Sale::STATUSES)],
            'payment_status' => ['nullable', 'string', Rule::in(Sale::PAYMENT_STATUSES)],
            'payment_method' => ['nullable', 'string', Rule::in(Sale::PAYMENT_METHODS)],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:8'],
            'delivery_method' => ['nullable', 'string', 'max:64'],
            'destination' => ['nullable', 'string', 'max:255'],
            'movement_permit_id' => ['nullable', 'integer', 'exists:movement_permits,id'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'attachment' => ['nullable', 'file', 'max:8192'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.animal_id' => ['nullable', 'integer', 'exists:animals,id'],
            'lines.*.livestock_id' => ['nullable', 'integer', 'exists:livestock,id'],
            'lines.*.sale_price' => ['nullable', 'numeric', 'min:0'],
            'lines.*.live_weight' => ['nullable', 'numeric', 'min:0'],
            'lines.*.price_per_kg' => ['nullable', 'numeric', 'min:0'],
            'lines.*.animal_condition' => ['nullable', 'string', Rule::in(SaleAnimal::CONDITIONS)],
            'lines.*.remarks' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
