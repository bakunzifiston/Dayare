<?php

namespace App\Http\Requests;

use App\Models\ColdRoom;
use App\Models\Demand;
use App\Models\Facility;
use App\Models\WarehouseStorage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWarehouseStorageRequest extends FormRequest
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
        $user = $this->user();
        $allowedUnits = $user
            ? $user->configuredUnitsForBusinessIds($user->accessibleBusinessIds())->pluck('code')->all()
            : [];
        $allowedUnits = empty($allowedUnits)
            ? array_keys(Demand::QUANTITY_UNITS)
            : array_values(array_unique(array_merge($allowedUnits, array_keys(Demand::QUANTITY_UNITS))));

        return [
            'warehouse_facility_id' => ['required', 'integer', 'exists:facilities,id'],
            'cold_room_id' => [
                'nullable',
                'integer',
                Rule::exists('cold_rooms', 'id')->where(
                    fn ($q) => $q->where('facility_id', (int) $this->input('warehouse_facility_id'))
                ),
            ],
            'storage_location' => ['nullable', 'string', 'max:255'],
            'temperature_at_entry' => ['nullable', 'numeric', 'min:-50', 'max:50'],
            'quantity_stored' => ['required', 'numeric', 'min:0'],
            'quantity_unit' => ['required', 'string', Rule::in($allowedUnits)],
            'status' => ['required', Rule::in(WarehouseStorage::STATUSES)],
            'released_date' => ['nullable', 'required_if:status,released', 'date'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $user = $this->user();
            if (! $user) {
                return;
            }

            // --- Section 2 ---
            $facilityIds = Facility::whereIn('business_id', $user->accessibleBusinessIds())->pluck('id');
            $facilityId = $this->input('warehouse_facility_id');

            if ($facilityId && ! $facilityIds->contains((int) $facilityId)) {
                $validator->errors()->add('warehouse_facility_id', __('Invalid facility.'));
            }

            if ($facilityId) {
                $facilityHasRooms = ColdRoom::where('facility_id', $facilityId)->exists();
                if ($facilityHasRooms && ! $this->input('cold_room_id')) {
                    $validator->errors()->add(
                        'cold_room_id',
                        __('This facility has cold rooms registered. Please select a cold room.')
                    );
                }
            }
        });
    }
}
