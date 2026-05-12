<?php

namespace App\Http\Requests\Farmer;

use App\Models\FeedSupplier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFeedSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'business_id' => ['required', 'integer', 'exists:businesses,id'],
            'supplier_name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:5000'],
            'supplied_feed_types' => ['nullable', 'array'],
            'supplied_feed_types.*' => ['integer', 'exists:feed_types,id'],
            'status' => ['required', 'string', Rule::in(FeedSupplier::STATUSES)],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
