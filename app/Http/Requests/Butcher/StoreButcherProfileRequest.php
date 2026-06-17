<?php

namespace App\Http\Requests\Butcher;

use App\Http\Requests\Butcher\Concerns\ResolvesButcherBusiness;
use App\Models\Business;
use App\Rules\RwandaDistrictName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreButcherProfileRequest extends FormRequest
{
    use ResolvesButcherBusiness;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $business = $this->butcherBusiness();

        return [
            'business_name' => ['required', 'string', 'max:255'],
            'butchery_type' => ['required', 'string', Rule::in(Business::BUTCHERY_TYPES)],
            'rdb_registration_number' => [
                'required',
                'string',
                'max:120',
                Rule::unique('businesses', 'registration_number')->ignore($business->id),
            ],
            'tin_number' => [
                'required',
                'string',
                'regex:/^\d{10}$/',
                Rule::unique('businesses', 'tax_id')->ignore($business->id),
            ],
            'phone' => ['required', 'string', 'regex:/^\+2507[0-9]{8}$/'],
            'rfa_permit_number' => ['nullable', 'string', 'max:120'],
            'rfa_permit_expiry' => ['nullable', 'date'],
            'district' => ['required', 'string', 'max:120', new RwandaDistrictName],
            'sector' => ['nullable', 'string', 'max:120'],
            'cell' => ['nullable', 'string', 'max:120'],
            'gps_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'gps_lng' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }
}
