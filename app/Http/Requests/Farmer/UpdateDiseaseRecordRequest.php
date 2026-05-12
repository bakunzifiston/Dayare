<?php

namespace App\Http\Requests\Farmer;

class UpdateDiseaseRecordRequest extends StoreDiseaseRecordRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        unset($rules['animal_id']);

        return $rules;
    }
}
