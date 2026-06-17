<?php

namespace App\Http\Requests\Butcher;

use App\Http\Requests\Butcher\Concerns\ResolvesButcherBusiness;
use App\Models\ButcherOrder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreButcherOrderRequest extends FormRequest
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
            'customer_id' => [
                'required',
                'integer',
                Rule::exists('butcher_customers', 'id')->where('business_id', $business->id),
            ],
            'order_date' => ['nullable', 'date'],
            'delivery_date' => ['nullable', 'date', 'after_or_equal:order_date'],
            'deposit_paid' => ['nullable', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => [
                'required',
                'integer',
                Rule::exists('butcher_products', 'id')->where('business_id', $business->id),
            ],
            'items.*.quantity_kg' => ['nullable', 'numeric', 'min:0'],
            'items.*.quantity_units' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
