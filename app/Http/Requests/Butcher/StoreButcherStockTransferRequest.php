<?php

namespace App\Http\Requests\Butcher;

use App\Http\Requests\Butcher\Concerns\ResolvesButcherBusiness;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreButcherStockTransferRequest extends FormRequest
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
            'from_outlet_id' => [
                'required',
                'integer',
                Rule::exists('butcher_outlets', 'id')->where('business_id', $business->id),
            ],
            'to_outlet_id' => [
                'required',
                'integer',
                'different:from_outlet_id',
                Rule::exists('butcher_outlets', 'id')->where('business_id', $business->id),
            ],
            'batch_id' => [
                'required',
                'integer',
                Rule::exists('butcher_inventory_batches', 'id')->where('business_id', $business->id),
            ],
            'quantity_kg' => ['required', 'numeric', 'min:0.001'],
            'storage_location' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'transferred_at' => ['nullable', 'date'],
        ];
    }
}
