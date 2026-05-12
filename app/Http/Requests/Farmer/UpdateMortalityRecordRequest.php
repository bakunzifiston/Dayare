<?php

namespace App\Http\Requests\Farmer;

class UpdateMortalityRecordRequest extends StoreMortalityRecordRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        unset($rules['animal_id']);

        return $rules;
    }

    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        // Mortality records remain append-only; updates only correct metadata on the same record.
    }
}
