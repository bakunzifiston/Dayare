<?php

namespace App\Http\Requests\Farmer;

class UpdateFeedTypeRequest extends StoreFeedTypeRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        unset($rules['business_id']);

        return $rules;
    }
}
