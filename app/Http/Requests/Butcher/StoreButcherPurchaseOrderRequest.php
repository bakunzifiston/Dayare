<?php

namespace App\Http\Requests\Butcher;

use App\Http\Requests\Butcher\Concerns\ResolvesButcherBusiness;
use App\Http\Requests\Butcher\Concerns\ValidatesButcherSupplierOwnership;
use App\Models\ButcherPurchaseOrder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreButcherPurchaseOrderRequest extends FormRequest
{
    use ResolvesButcherBusiness;
    use ValidatesButcherSupplierOwnership;

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
            'supplier_id' => $this->activeSupplierRule((int) $business->id),
            'meat_type' => ['required', 'string', Rule::in(ButcherPurchaseOrder::MEAT_TYPES)],
            'requested_weight_kg' => ['required', 'numeric', 'min:0.1'],
            'requested_date' => ['required', 'date', 'after_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
