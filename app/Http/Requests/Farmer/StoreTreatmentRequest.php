<?php

namespace App\Http\Requests\Farmer;

use App\Http\Requests\Farmer\Concerns\ValidatesHealthAttachment;
use App\Models\Treatment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreTreatmentRequest extends FormRequest
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
            'disease_name' => ['nullable', 'string', 'max:255'],
            'symptoms' => ['nullable', 'string', 'max:5000'],
            'diagnosis' => ['nullable', 'string', 'max:5000'],
            'medicine_name' => ['nullable', 'string', 'max:255'],
            'dosage' => ['nullable', 'string', 'max:120'],
            'treatment_method' => ['nullable', 'string', 'max:120'],
            'treatment_start_date' => ['required', 'date', 'before_or_equal:today'],
            'treatment_end_date' => ['nullable', 'date', 'after_or_equal:treatment_start_date'],
            'veterinarian_name' => ['nullable', 'string', 'max:255'],
            'response_to_treatment' => ['nullable', 'string', 'max:255'],
            'follow_up_date' => ['nullable', 'date', 'after_or_equal:treatment_start_date'],
            'status' => ['required', 'string', Rule::in(Treatment::STATUSES)],
            'notes' => ['nullable', 'string', 'max:5000'],
        ], $this->healthAttachmentRules());
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->input('status') !== Treatment::STATUS_ONGOING) {
                return;
            }

            $animalId = (int) $this->input('animal_id');
            if ($animalId <= 0) {
                return;
            }

            $exists = Treatment::query()
                ->where('animal_id', $animalId)
                ->where('status', Treatment::STATUS_ONGOING)
                ->exists();

            if ($exists) {
                $validator->errors()->add('status', __('This animal already has an ongoing treatment.'));
            }
        });
    }
}
