<?php

namespace App\Http\Requests;

use App\Models\Demand;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDemandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $businessId = (int) $this->input('business_id');
        $allowedSpecies = $this->user()?->configuredSpeciesNames([$businessId])->all() ?? [];
        $allowedUnits = $this->user()?->configuredUnitsForBusinessIds([$businessId])->pluck('code')->all() ?? [];

        return [
            'business_id' => ['required', 'exists:businesses,id'],
            'demand_number' => ['required', 'string', 'max:100'],
            'title' => ['required', 'string', 'max:255'],
            'destination_facility_id' => ['required_without_all:client_id,client_name', 'nullable', 'exists:facilities,id'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'contract_id' => ['nullable', 'exists:contracts,id'],
            'client_name' => ['required_without_all:destination_facility_id,client_id', 'nullable', 'string', 'max:255'],
            'client_company' => ['nullable', 'string', 'max:255'],
            'client_country' => ['nullable', 'string', 'max:100'],
            'client_contact' => ['nullable', 'string', 'max:255'],
            'client_address' => ['nullable', 'string'],
            'species' => ['required', 'string', 'max:50', Rule::in($allowedSpecies)],
            'product_description' => ['nullable', 'string'],
            'quantity' => ['required', 'numeric', 'min:0'],
            'quantity_unit' => ['required', 'string', Rule::in($allowedUnits)],
            'requested_delivery_date' => ['required', 'date'],
            'status' => ['required', 'string', 'in:'.implode(',', array_keys(Demand::STATUSES))],
            'notes' => ['nullable', 'string'],
            'fulfilled_by_delivery_id' => ['nullable', 'exists:delivery_confirmations,id'],
        ];
    }
}
