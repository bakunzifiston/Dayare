<?php

namespace App\Http\Requests\Farmer;

use App\Models\FeedInventory;
use App\Models\FeedingRecord;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreFeedingRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'target_type' => ['required', 'string', Rule::in(['animal', 'livestock'])],
            'animal_id' => ['nullable', 'integer', 'exists:animals,id', 'required_if:target_type,animal'],
            'livestock_id' => ['nullable', 'integer', 'exists:livestock,id', 'required_if:target_type,livestock'],
            'feed_type_id' => ['required', 'integer', 'exists:feed_types,id'],
            'feed_inventory_id' => ['required', 'integer', 'exists:feed_inventories,id'],
            'quantity' => ['required', 'numeric', 'min:0.001'],
            'feeding_method' => ['nullable', 'string', Rule::in(FeedingRecord::METHODS)],
            'feeding_time' => ['nullable', 'date_format:H:i'],
            'feeding_date' => ['required', 'date', 'before_or_equal:today'],
            'fed_by' => ['nullable', 'string', 'max:255'],
            'appetite_status' => ['nullable', 'string', Rule::in(FeedingRecord::APPETITE_STATUSES)],
            'water_provided' => ['sometimes', 'boolean'],
            'feeding_response' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $inventoryId = (int) $this->input('feed_inventory_id');
            $quantity = (float) $this->input('quantity');
            if ($inventoryId <= 0 || $quantity <= 0) {
                return;
            }

            $inventory = FeedInventory::query()->find($inventoryId);
            if (! $inventory) {
                return;
            }

            if ($inventory->status === FeedInventory::STATUS_EXPIRED) {
                $validator->errors()->add('feed_inventory_id', __('Expired feed cannot be used.'));
            }

            if ($inventory->expiry_date !== null && $this->input('feeding_date') && $inventory->expiry_date->lt($this->date('feeding_date'))) {
                $validator->errors()->add('feed_inventory_id', __('Feed batch was expired before the feeding date.'));
            }

            if ($quantity > (float) $inventory->quantity_remaining) {
                $validator->errors()->add('quantity', __('Quantity exceeds available inventory.'));
            }
        });
    }
}
