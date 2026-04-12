<?php

namespace App\Http\Requests\Farmer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAnimalHealthRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'livestock_id' => ['nullable', 'exists:livestock,id'],
            'record_date' => ['required', 'date'],
            'condition' => ['required', 'string', Rule::in(\App\Models\AnimalHealthRecord::CONDITIONS)],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
