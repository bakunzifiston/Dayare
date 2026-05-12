<?php

namespace App\Http\Requests\Farmer;

class UpdateVeterinaryVisitRequest extends StoreVeterinaryVisitRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        unset($rules['animal_id']);

        return $rules;
    }
}
