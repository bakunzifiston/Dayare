<?php

namespace App\Http\Requests\Butcher;

use App\Http\Requests\Butcher\Concerns\ResolvesButcherBusiness;
use App\Models\ButcherCutType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreButcherCutTypeRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:120'],
            'meat_type' => ['required', 'string', Rule::in(ButcherCutType::MEAT_TYPES)],
            'expected_yield_pct' => ['required', 'numeric', 'min:0.01', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
