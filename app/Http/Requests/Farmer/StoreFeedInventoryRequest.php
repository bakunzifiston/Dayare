<?php

namespace App\Http\Requests\Farmer;

use Illuminate\Foundation\Http\FormRequest;

class StoreFeedInventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'feed_type_id' => ['required', 'integer', 'exists:feed_types,id'],
            'supplier_id' => ['nullable', 'integer', 'exists:feed_suppliers,id'],
            'quantity_received' => ['required', 'numeric', 'min:0.001'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'purchase_date' => ['required', 'date', 'before_or_equal:today'],
            'expiry_date' => ['nullable', 'date', 'after_or_equal:purchase_date'],
            'storage_location' => ['nullable', 'string', 'max:255'],
            'reorder_level' => ['nullable', 'numeric', 'min:0'],
            'batch_number' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
