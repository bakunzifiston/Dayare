<?php

namespace App\Http\Requests;

use App\Models\ColdRoom;
use App\Models\Demand;
use App\Models\Facility;
use App\Support\StorablePostMortemMeat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWarehouseStorageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $unit = $this->input('quantity_unit');
        if ($unit === null || $unit === '') {
            $unit = 'kg';
        }

        $this->merge([
            'entry_date' => $this->input('entry_date') ?: now()->toDateString(),
            'quantity_unit' => $unit,
        ]);
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
            : array_values(array_unique(array_merge(['kg'], $allowedUnits, array_keys(Demand::QUANTITY_UNITS))));

        return [
            'warehouse_facility_id' => ['required', 'integer', 'exists:facilities,id'],
            'cold_room_id' => [
                'nullable',
                'integer',
                Rule::exists('cold_rooms', 'id')->where(
                    fn ($q) => $q->where('facility_id', (int) $this->input('warehouse_facility_id'))
                ),
            ],
            'post_mortem_inspection_item_ids' => ['required', 'array', 'min:1'],
            'post_mortem_inspection_item_ids.*' => ['integer', 'distinct', 'exists:post_mortem_inspection_items,id'],
            'quantities' => ['nullable', 'array'],
            'quantities.*' => ['nullable', 'numeric', 'min:0.01', 'max:99999'],
            'entry_date' => ['required', 'date'],
            'storage_location' => ['nullable', 'string', 'max:255'],
            'temperature_at_entry' => ['nullable', 'numeric', 'min:-50', 'max:50'],
            'quantity_unit' => ['required', 'string', Rule::in($allowedUnits)],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $user = $this->user();
            if (! $user) {
                return;
            }

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

            $requestedIds = collect($this->input('post_mortem_inspection_item_ids', []))
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();

            if ($requestedIds->isEmpty()) {
                return;
            }

            $storableItems = StorablePostMortemMeat::findStorableItems($this, $requestedIds->all());

            if ($storableItems->count() !== $requestedIds->count()) {
                $validator->errors()->add(
                    'post_mortem_inspection_item_ids',
                    __('One or more animals are not available for cold storage (not approved at post-mortem or already stored).')
                );
            }

            foreach ($storableItems as $item) {
                $qty = $this->input('quantities.'.$item->id);
                $meatKg = StorablePostMortemMeat::meatKgForItem($item);

                if (($qty === null || $qty === '') && $meatKg <= 0) {
                    $validator->errors()->add(
                        'quantities.'.$item->id,
                        __('Post-mortem meat weight is missing for :tag.', [
                            'tag' => $item->intakeItem?->ear_tag ?: __('animal'),
                        ])
                    );
                }
            }
        });
    }
}
