<?php

namespace App\Http\Requests;

use App\Models\Business;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBusinessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Business $business */
        $business = $this->route('business');

        return [
            'business_name' => ['required', 'string', 'max:255'],
            'registration_number' => ['required', 'string', 'max:100', Rule::unique('businesses', 'registration_number')->ignore($business->id)],
            'tax_id' => ['nullable', 'string', 'max:100'],
            'contact_phone' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255'],
            'status' => ['required', 'string', Rule::in(Business::STATUSES)],
        ];
    }
}
