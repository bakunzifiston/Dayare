<?php

namespace App\Http\Requests\Butcher;

use App\Http\Requests\Butcher\Concerns\ResolvesButcherBusiness;
use App\Models\ButcherSale;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreButcherReturnRequest extends FormRequest
{
    use ResolvesButcherBusiness;

    public function authorize(): bool
    {
        $sale = $this->route('sale');
        if (! $sale instanceof ButcherSale || $this->user() === null) {
            return false;
        }

        abort_unless((int) $sale->business_id === (int) $this->butcherBusiness()->id, 404);

        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $sale = $this->route('sale');

        return [
            'sale_item_id' => [
                'required',
                'integer',
                Rule::exists('butcher_sale_items', 'id')->where('sale_id', $sale instanceof ButcherSale ? $sale->id : 0),
            ],
            'quantity_kg' => ['required', 'numeric', 'min:0.001'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
