<?php

namespace App\Http\Requests\Butcher;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class UpdateButcherBusinessRequest extends FormRequest
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
            'business_name' => ['required', 'string', 'max:255'],
            'registration_number' => ['required', 'string', 'max:120'],
            'contact_phone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'district_id' => ['nullable', 'integer', 'exists:districts,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'business_name' => trim((string) $this->input('business_name', '')),
            'registration_number' => trim((string) $this->input('registration_number', '')),
            'contact_phone' => trim((string) $this->input('contact_phone', '')),
            'email' => $this->filled('email') ? Str::lower(trim((string) $this->input('email'))) : null,
        ]);
    }
}
