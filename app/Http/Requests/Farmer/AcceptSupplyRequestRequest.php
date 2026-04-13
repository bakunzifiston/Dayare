<?php

namespace App\Http\Requests\Farmer;

use Illuminate\Foundation\Http\FormRequest;

class AcceptSupplyRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'farm_id' => ['required', 'integer', 'exists:farms,id'],
            'movement_permit_id' => ['required', 'integer', 'exists:movement_permits,id'],
        ];
    }
}
