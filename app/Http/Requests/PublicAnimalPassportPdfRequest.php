<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PublicAnimalPassportPdfRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'identifier' => ['required', 'string', 'min:2', 'max:255'],
        ];
    }

    public function identifier(): string
    {
        return trim((string) $this->validated('identifier'));
    }
}
