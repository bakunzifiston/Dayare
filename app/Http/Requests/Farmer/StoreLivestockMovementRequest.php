<?php

namespace App\Http\Requests\Farmer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLivestockMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'destination_farm_id' => ['required', 'integer', 'exists:farms,id', 'different:source_farm_id'],
            'source_farm_id' => ['required', 'integer', 'exists:farms,id'],
            'livestock_id' => ['required', 'integer', 'exists:livestock,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', Rule::in(['sale', 'transfer', 'loss'])],
            'movement_date' => ['required', 'date'],
            'movement_permit_id' => ['required', 'integer', 'exists:movement_permits,id'],
        ];
    }
}

