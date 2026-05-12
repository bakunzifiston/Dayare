<?php

namespace App\Http\Requests\Farmer;

class UpdateVaccinationRequest extends StoreVaccinationRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        unset($rules['animal_id']);

        return $rules;
    }
}
