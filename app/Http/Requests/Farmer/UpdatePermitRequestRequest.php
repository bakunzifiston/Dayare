<?php

namespace App\Http\Requests\Farmer;

class UpdatePermitRequestRequest extends StorePermitRequestRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['proposed_departure_date'] = ['required', 'date'];
        unset($rules['farm_id']);

        return $rules;
    }
}
