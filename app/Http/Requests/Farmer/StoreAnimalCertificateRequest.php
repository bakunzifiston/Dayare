<?php

namespace App\Http\Requests\Farmer;

use App\Models\AnimalCertificate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreAnimalCertificateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'animal_id' => ['required', 'integer', 'exists:animals,id'],
            'template_id' => ['nullable', 'integer', 'exists:animal_certificate_templates,id'],
            'certificate_type' => ['required', 'string', Rule::in(AnimalCertificate::TYPES)],
            'certificate_title' => ['nullable', 'string', 'max:255'],
            'issue_date' => ['required', 'date', 'before_or_equal:today'],
            'expiry_date' => ['nullable', 'date', 'after:issue_date'],
            'issued_by' => ['nullable', 'string', 'max:255'],
            'veterinarian_name' => ['nullable', 'string', 'max:255'],
            'remarks' => ['nullable', 'string', 'max:5000'],
            'activate' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->boolean('activate') === false && $this->input('certificate_status') !== AnimalCertificate::STATUS_ACTIVE) {
                return;
            }

            $animalId = (int) $this->input('animal_id');
            $type = (string) $this->input('certificate_type');
            if ($animalId <= 0 || $type === '') {
                return;
            }

            $exists = AnimalCertificate::query()
                ->where('animal_id', $animalId)
                ->where('certificate_type', $type)
                ->where('certificate_status', AnimalCertificate::STATUS_ACTIVE)
                ->exists();

            if ($exists && $this->boolean('activate', true)) {
                $validator->errors()->add('certificate_type', __('An active certificate of this type already exists for the animal.'));
            }
        });
    }
}
