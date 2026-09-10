<?php

namespace App\Http\Requests\Butcher;

use App\Http\Requests\Butcher\Concerns\ResolvesButcherBusiness;
use App\Models\Business;
use App\Rules\RwandaDistrictName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateButcherBusinessRequest extends FormRequest
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
            'registration_number' => [
                'required',
                'string',
                'max:120',
                Rule::unique('businesses', 'registration_number')->ignore($business->id),
            ],
            'tax_id' => [
                'required',
                'string',
                'regex:/^\d{10}$/',
                Rule::unique('businesses', 'tax_id')->ignore($business->id),
            ],
            'contact_phone' => ['required', 'string', 'regex:/^\+2507[0-9]{8}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'rfa_permit_number' => ['nullable', 'string', 'max:120'],
            'rfa_permit_expiry' => ['nullable', 'date'],
            'butcher_district' => ['required', 'string', 'max:120', new RwandaDistrictName],
            'butcher_sector' => ['nullable', 'string', 'max:120'],
            'butcher_cell' => ['nullable', 'string', 'max:120'],
            'gps_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'gps_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'butcher_fresh_max_temp_c' => ['required', 'numeric', 'between:-50,50'],
            'butcher_frozen_max_temp_c' => ['required', 'numeric', 'between:-50,50'],
            'butcher_batch_shelf_life_days' => ['required', 'integer', 'min:1', 'max:365'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'business_name' => trim((string) $this->input('business_name', '')),
            'registration_number' => trim((string) $this->input('registration_number', '')),
            'tax_id' => trim((string) $this->input('tax_id', '')),
            'contact_phone' => trim((string) $this->input('contact_phone', '')),
            'email' => $this->filled('email') ? Str::lower(trim((string) $this->input('email'))) : null,
            'butcher_district' => trim((string) $this->input('butcher_district', $this->input('district', ''))),
            'butcher_sector' => $this->filled('butcher_sector')
                ? trim((string) $this->input('butcher_sector'))
                : ($this->filled('sector') ? trim((string) $this->input('sector')) : null),
            'butcher_cell' => $this->filled('butcher_cell')
                ? trim((string) $this->input('butcher_cell'))
                : ($this->filled('cell') ? trim((string) $this->input('cell')) : null),
        ]);
    }
}
