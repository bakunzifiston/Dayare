<?php

namespace App\Http\Requests\Butcher;

use Illuminate\Foundation\Http\FormRequest;

class UpdateButcherStockCountLinesRequest extends FormRequest
{
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
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.id' => ['required', 'integer'],
            'lines.*.counted_weight_kg' => ['nullable', 'numeric', 'min:0'],
            'lines.*.notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
