<?php

namespace App\Http\Requests\Butcher;

use App\Http\Requests\Butcher\Concerns\ResolvesButcherBusiness;
use App\Models\ButcherPriceRule;
use App\Models\ButcherProduct;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreButcherPriceRuleRequest extends FormRequest
{
    use ResolvesButcherBusiness;

    public function authorize(): bool
    {
        if ($this->user() === null) {
            return false;
        }

        $product = $this->route('product');
        if ($product instanceof ButcherProduct) {
            abort_unless((int) $product->business_id === (int) $this->butcherBusiness()->id, 404);
        }

        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $business = $this->butcherBusiness();

        return [
            'product_id' => [
                'required',
                'integer',
                Rule::exists('butcher_products', 'id')->where('business_id', $business->id),
            ],
            'outlet_id' => [
                'nullable',
                'integer',
                Rule::exists('butcher_outlets', 'id')->where('business_id', $business->id),
            ],
            'customer_tier' => ['nullable', 'string', Rule::in(ButcherPriceRule::CUSTOMER_TIERS)],
            'price' => ['required', 'numeric', 'min:0'],
            'valid_from' => ['required', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $product = $this->route('product');
        if ($product instanceof ButcherProduct) {
            $this->merge(['product_id' => $product->id]);
        }

        if ($this->has('is_active')) {
            $this->merge(['is_active' => $this->boolean('is_active')]);
        }

        if ($this->input('outlet_id') === '') {
            $this->merge(['outlet_id' => null]);
        }

        if ($this->input('customer_tier') === '') {
            $this->merge(['customer_tier' => null]);
        }
    }
}
