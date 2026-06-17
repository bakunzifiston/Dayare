<?php

namespace App\Http\Requests\Butcher;

use App\Http\Requests\Butcher\Concerns\ResolvesButcherBusiness;
use App\Models\ButcherCutType;
use App\Models\ButcherProduct;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreButcherProductRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:160'],
            'cut_type_id' => [
                'nullable',
                'integer',
                Rule::exists('butcher_cut_types', 'id')->where('business_id', $business->id),
            ],
            'meat_type' => ['required', 'string', Rule::in(ButcherProduct::MEAT_TYPES)],
            'unit' => ['required', 'string', Rule::in(ButcherProduct::UNITS)],
            'default_price' => ['required', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
