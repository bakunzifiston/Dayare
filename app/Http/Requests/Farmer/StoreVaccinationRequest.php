<?php

namespace App\Http\Requests\Farmer;

use App\Http\Requests\Farmer\Concerns\ValidatesHealthAttachment;
use App\Models\Vaccination;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVaccinationRequest extends FormRequest
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
            'vaccine_name' => ['required', 'string', 'max:255'],
            'vaccine_type' => ['nullable', 'string', 'max:120'],
            'manufacturer' => ['nullable', 'string', 'max:255'],
            'batch_number' => ['nullable', 'string', 'max:120'],
            'dosage' => ['nullable', 'string', 'max:120'],
            'administration_method' => ['nullable', 'string', 'max:120'],
            'vaccination_date' => ['required', 'date', 'before_or_equal:today'],
            'next_due_date' => ['nullable', 'date', 'after:vaccination_date'],
            'veterinarian_name' => ['nullable', 'string', 'max:255'],
            'veterinary_clinic' => ['nullable', 'string', 'max:255'],
            'administered_by' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', Rule::in(Vaccination::STATUSES)],
            'side_effects' => ['nullable', 'string', 'max:5000'],
            'reaction_notes' => ['nullable', 'string', 'max:5000'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ], $this->healthAttachmentRules());
    }
}
