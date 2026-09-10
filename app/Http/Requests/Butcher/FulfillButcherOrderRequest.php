<?php

namespace App\Http\Requests\Butcher;

use App\Http\Requests\Butcher\Concerns\ResolvesButcherBusiness;
use App\Models\ButcherSale;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FulfillButcherOrderRequest extends FormRequest
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
            'payment_method' => ['required', 'string', Rule::in(ButcherSale::PAYMENT_METHODS)],
            'amount_paid' => ['nullable', 'numeric', 'min:0'],
            'split_payments' => ['nullable', 'array'],
            'split_payments.*.payment_method' => ['required_with:split_payments', 'string', Rule::in(['cash', 'momo', 'card'])],
            'split_payments.*.amount' => ['required_with:split_payments', 'numeric', 'min:0'],
            'safety_override_reason' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
