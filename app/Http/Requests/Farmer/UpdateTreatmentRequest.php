<?php

namespace App\Http\Requests\Farmer;

use App\Models\Treatment;
use Illuminate\Validation\Validator;

class UpdateTreatmentRequest extends StoreTreatmentRequest
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
            if ($this->input('status') !== Treatment::STATUS_ONGOING) {
                return;
            }

            $treatment = $this->route('treatment');
            if (! $treatment instanceof Treatment) {
                return;
            }

            $exists = Treatment::query()
                ->where('animal_id', $treatment->animal_id)
                ->where('status', Treatment::STATUS_ONGOING)
                ->whereKeyNot($treatment->id)
                ->exists();

            if ($exists) {
                $validator->errors()->add('status', __('This animal already has an ongoing treatment.'));
            }
        });
    }
}
