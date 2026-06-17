<?php

namespace App\Http\Requests\Butcher;

use App\Http\Requests\Butcher\Concerns\ResolvesButcherBusiness;
use App\Models\ButcherInventoryBatch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreButcherCuttingSessionRequest extends FormRequest
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
            'outlet_id' => [
                'required',
                'integer',
                Rule::exists('butcher_outlets', 'id')->where('business_id', $business->id),
            ],
            'batch_id' => [
                'required',
                'integer',
                Rule::exists('butcher_inventory_batches', 'id')->where(function ($query) use ($business) {
                    $query->where('business_id', $business->id)
                        ->whereIn('status', ButcherInventoryBatch::ACTIVE_STATUSES);
                }),
            ],
            'source_weight_kg' => ['required', 'numeric', 'min:0.1'],
            'session_date' => ['nullable', 'date'],
        ];
    }
}
