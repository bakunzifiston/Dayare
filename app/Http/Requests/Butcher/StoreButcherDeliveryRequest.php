<?php

namespace App\Http\Requests\Butcher;

use App\Http\Requests\Butcher\Concerns\ResolvesButcherBusiness;
use App\Http\Requests\Butcher\Concerns\ValidatesButcherSupplierOwnership;
use App\Models\ButcherDelivery;
use App\Models\ButcherPurchaseOrder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreButcherDeliveryRequest extends FormRequest
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
            'purchase_order_id' => [
                'nullable',
                'integer',
                Rule::exists('butcher_purchase_orders', 'id')->where('business_id', $business->id),
            ],
            'supplier_id' => $this->activeSupplierRule((int) $business->id),
            'outlet_id' => [
                'required',
                'integer',
                Rule::exists('butcher_outlets', 'id')->where('business_id', $business->id),
            ],
            'meat_type' => ['required', 'string', Rule::in(ButcherDelivery::MEAT_TYPES)],
            'received_weight_kg' => ['required', 'numeric', 'min:0.1'],
            'unit_cost_per_kg' => ['required', 'numeric', 'min:0'],
            'condition' => ['required', 'string', Rule::in(ButcherDelivery::CONDITIONS)],
            'received_at' => ['nullable', 'date'],
            'certificate_ref' => ['nullable', 'string', 'max:100'],
            'certificate_issuer' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('purchase_order_id')) {
            $order = ButcherPurchaseOrder::query()->find($this->input('purchase_order_id'));
            if ($order !== null && ! $this->filled('supplier_id')) {
                $this->merge(['supplier_id' => $order->supplier_id]);
            }
            if ($order !== null && ! $this->filled('meat_type')) {
                $this->merge(['meat_type' => $order->meat_type]);
            }
        }
    }
}
