<?php

namespace App\Http\Requests\Butcher;

use App\Http\Requests\Butcher\Concerns\ResolvesButcherBusiness;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreButcherStockCountRequest extends FormRequest
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
                'nullable',
                'integer',
                Rule::exists('butcher_outlets', 'id')->where('business_id', $business->id),
            ],
            'count_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
