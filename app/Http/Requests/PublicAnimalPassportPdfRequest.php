<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

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

    protected function failedValidation(Validator $validator): void
    {
        if ($this->isMethod('GET')) {
            throw new HttpResponseException(
                redirect()->route('animal.passport.lookup')->withInput($this->query())->withErrors($validator)
            );
        }

        parent::failedValidation($validator);
    }
}
