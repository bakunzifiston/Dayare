<?php

namespace App\Http\Requests\Farmer;

use App\Http\Requests\Farmer\Concerns\ValidatesHealthAttachment;
use App\Models\DiseaseRecord;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDiseaseRecordRequest extends FormRequest
{
    use ValidatesHealthAttachment;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return array_merge([
            'animal_id' => ['required', 'integer', 'exists:animals,id'],
            'disease_name' => ['required', 'string', 'max:255'],
            'symptoms' => ['nullable', 'string', 'max:5000'],
            'severity_level' => ['required', 'string', Rule::in(DiseaseRecord::SEVERITY_LEVELS)],
            'diagnosis_date' => ['required', 'date', 'before_or_equal:today'],
            'quarantine_required' => ['sometimes', 'boolean'],
            'contagious_status' => ['required', 'string', Rule::in(DiseaseRecord::CONTAGIOUS_STATUSES)],
            'recovery_status' => ['required', 'string', Rule::in(DiseaseRecord::RECOVERY_STATUSES)],
            'veterinarian_name' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ], $this->healthAttachmentRules());
    }
}
