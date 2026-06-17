<?php

namespace App\Http\Requests\Butcher;

use App\Models\ButcherSupplier;
use App\Rules\RwandaDistrictName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreButcherSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'regex:/^\+2507[0-9]{8}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'supplier_type' => ['required', 'string', Rule::in(ButcherSupplier::SUPPLIER_TYPES)],
            'district' => ['nullable', 'string', 'max:120', new RwandaDistrictName],
            'sector' => ['nullable', 'string', 'max:120'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
