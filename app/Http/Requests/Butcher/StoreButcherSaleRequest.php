<?php

namespace App\Http\Requests\Butcher;

use App\Http\Requests\Butcher\Concerns\ResolvesButcherBusiness;
use App\Models\ButcherProduct;
use App\Models\ButcherSale;
use App\Models\ButcherSalePayment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreButcherSaleRequest extends FormRequest
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
            'customer_id' => [
                'nullable',
                'integer',
                Rule::exists('butcher_customers', 'id')->where('business_id', $business->id),
            ],
            'sale_date' => ['nullable', 'date'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['required', 'string', Rule::in(ButcherSale::PAYMENT_METHODS)],
            'amount_paid' => ['nullable', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => [
                'required',
                'integer',
                Rule::exists('butcher_products', 'id')->where('business_id', $business->id),
            ],
            'items.*.cut_output_id' => ['nullable', 'integer'],
            'items.*.quantity_kg' => ['nullable', 'numeric', 'min:0'],
            'items.*.quantity_units' => ['nullable', 'integer', 'min:1'],
            'split_payments' => ['nullable', 'array'],
            'split_payments.*.payment_method' => ['string', Rule::in(ButcherSalePayment::SPLIT_METHODS)],
            'split_payments.*.amount' => ['numeric', 'min:0'],
        ];
    }
}
