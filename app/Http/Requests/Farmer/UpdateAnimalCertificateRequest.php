<?php

namespace App\Http\Requests\Farmer;

use App\Models\AnimalCertificate;
use Illuminate\Validation\Validator;

class UpdateAnimalCertificateRequest extends StoreAnimalCertificateRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        unset($rules['animal_id']);

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $certificate = $this->route('certificate');
            if (! $certificate instanceof AnimalCertificate) {
                return;
            }

            if ($this->input('certificate_type', $certificate->certificate_type) !== $certificate->certificate_type
                && $this->boolean('activate', false)) {
                $exists = AnimalCertificate::query()
                    ->where('animal_id', $certificate->animal_id)
                    ->where('certificate_type', $this->input('certificate_type'))
                    ->where('certificate_status', AnimalCertificate::STATUS_ACTIVE)
                    ->whereKeyNot($certificate->id)
                    ->exists();

                if ($exists) {
                    $validator->errors()->add('certificate_type', __('An active certificate of this type already exists for the animal.'));
                }
            }
        });
    }
}
