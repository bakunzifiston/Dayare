<?php

namespace App\Http\Requests\Butcher;

use Illuminate\Foundation\Http\FormRequest;

class CompleteButcherStockCountRequest extends FormRequest
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
            'apply_variances' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'apply_variances' => $this->boolean('apply_variances'),
        ]);
    }
}
