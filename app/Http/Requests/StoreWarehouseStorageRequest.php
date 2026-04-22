<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWarehouseStorageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'warehouse_facility_id' => ['required', 'integer', 'exists:facilities,id'],
            'cold_room_id' => [
                'nullable',
                'integer',
                Rule::exists('cold_rooms', 'id')->where(
                    fn ($q) => $q->where('facility_id', (int) $this->input('warehouse_facility_id'))
                ),
            ],
            'certificate_id' => ['required', 'integer', 'exists:certificates,id'],
            'entry_date' => ['required', 'date'],
            'storage_location' => ['nullable', 'string', 'max:255'],
            'temperature_at_entry' => ['nullable', 'numeric', 'min:-50', 'max:50'],
            'quantity_stored' => ['required', 'integer', 'min:1'],
            'quantity_unit' => ['required', 'string'],
        ];
    }
}
