<?php

namespace App\Http\Requests\Butcher;

use App\Http\Requests\Butcher\Concerns\ResolvesButcherBusiness;
use App\Models\ButcherInventoryAdjustment;
use App\Models\ButcherInventoryBatch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreButcherInventoryAdjustmentRequest extends FormRequest
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
            'batch_id' => [
                'required',
                'integer',
                Rule::exists('butcher_inventory_batches', 'id')->where(function ($query) use ($business) {
                    $query->where('business_id', $business->id)
                        ->whereIn('status', [
                            ButcherInventoryBatch::STATUS_IN_STORAGE,
                            ButcherInventoryBatch::STATUS_PARTIALLY_USED,
                            ButcherInventoryBatch::STATUS_EXPIRED,
                            ButcherInventoryBatch::STATUS_FULLY_USED,
                        ]);
                }),
            ],
            'weight_change_kg' => ['required', 'numeric', 'not_in:0'],
            'reason' => ['required', 'string', Rule::in(ButcherInventoryAdjustment::REASONS)],
            'adjusted_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
