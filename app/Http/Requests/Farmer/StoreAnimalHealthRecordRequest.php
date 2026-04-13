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
            'livestock_id' => ['nullable', 'exists:livestock,id', 'required_without:batch_reference'],
            'batch_reference' => ['nullable', 'string', 'max:100', 'required_without:livestock_id'],
            'record_date' => ['required', 'date'],
            'event_type' => ['required', 'string', Rule::in(\App\Models\AnimalHealthRecord::EVENT_TYPES)],
            'condition' => ['required', 'string', Rule::in(\App\Models\AnimalHealthRecord::CONDITIONS)],
            'next_due_date' => ['nullable', 'date', 'after_or_equal:record_date'],
            'treatment_given' => ['nullable', 'string', 'max:5000'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
