<?php

namespace App\Http\Requests\Butcher;

use App\Models\ButcherPermit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreButcherPermitRequest extends FormRequest
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
            'permit_type' => ['required', 'string', Rule::in(ButcherPermit::PERMIT_TYPES)],
            'permit_number' => ['required', 'string', 'max:120'],
            'issued_by' => ['required', 'string', 'max:255'],
            'issue_date' => ['required', 'date', 'before_or_equal:today'],
            'expiry_date' => ['required', 'date', 'after:today'],
            'document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }
}
