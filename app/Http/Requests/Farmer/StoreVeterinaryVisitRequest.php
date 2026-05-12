<?php

namespace App\Http\Requests\Farmer;

use App\Http\Requests\Farmer\Concerns\ValidatesHealthAttachment;
use Illuminate\Foundation\Http\FormRequest;

class StoreVeterinaryVisitRequest extends FormRequest
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
            'visit_date' => ['required', 'date', 'before_or_equal:today'],
            'veterinarian_name' => ['nullable', 'string', 'max:255'],
            'clinic_name' => ['nullable', 'string', 'max:255'],
            'purpose_of_visit' => ['nullable', 'string', 'max:255'],
            'findings' => ['nullable', 'string', 'max:5000'],
            'recommendations' => ['nullable', 'string', 'max:5000'],
            'follow_up_required' => ['sometimes', 'boolean'],
            'follow_up_date' => ['nullable', 'date', 'after_or_equal:visit_date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ], $this->healthAttachmentRules());
    }
}
