<?php

namespace App\Http\Requests\Farmer;

use App\Http\Requests\Farmer\Concerns\ValidatesHealthAttachment;
use App\Models\Animal;
use App\Models\MortalityRecord;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreMortalityRecordRequest extends FormRequest
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
            'death_date' => ['required', 'date', 'before_or_equal:today'],
            'cause_of_death' => ['required', 'string', 'max:255'],
            'reported_by' => ['nullable', 'string', 'max:255'],
            'postmortem_done' => ['sometimes', 'boolean'],
            'veterinarian_name' => ['nullable', 'string', 'max:255'],
            'disposal_method' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ], $this->healthAttachmentRules());
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $animalId = (int) $this->input('animal_id');
            if ($animalId <= 0) {
                return;
            }

            $animal = Animal::query()->find($animalId);
            if ($animal && $animal->lifecycle_status === Animal::LIFECYCLE_DEAD) {
                $validator->errors()->add('animal_id', __('This animal already has a mortality record.'));
            }

            if (MortalityRecord::query()->where('animal_id', $animalId)->exists()) {
                $validator->errors()->add('animal_id', __('A mortality record already exists for this animal.'));
            }
        });
    }
}
