<?php

namespace App\Http\Requests\Farmer;

use App\Models\Buyer;
use App\Models\Sale;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBuyerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $farmerIds = $this->user()->accessibleFarmerBusinessIds()->all();

        return [
            'business_id' => ['required', 'integer', Rule::in($farmerIds)],
            'buyer_name' => ['required', 'string', 'max:255'],
            'buyer_type' => ['required', 'string', Rule::in(Buyer::TYPES)],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'national_id' => ['nullable', 'string', 'max:50'],
            'company_registration' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:2000'],
            'preferred_payment_method' => ['nullable', 'string', Rule::in(Sale::PAYMENT_METHODS)],
            'trust_level' => ['required', 'string', Rule::in(Buyer::TRUST_LEVELS)],
            'notes' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', 'string', Rule::in(Buyer::STATUSES)],
        ];
    }
}
