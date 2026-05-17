<?php

namespace App\Http\Requests\Farmer;

use App\Models\Animal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAnimalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('acquisition_type') === '') {
            $this->merge(['acquisition_type' => null]);
        }
        if ($this->input('current_condition') === '') {
            $this->merge(['current_condition' => null]);
        }
    }

    public function rules(): array
    {
        $livestock = $this->route('livestock');
        $animal = $this->route('animal');

        return [
            'tag_number' => [
                'required',
                'string',
                'max:80',
                Rule::unique('animals', 'tag_number')
                    ->where(fn ($query) => $query->where('livestock_id', $livestock?->id))
                    ->ignore($animal?->id),
            ],
            'animal_name' => ['nullable', 'string', 'max:255'],
            'gender' => ['required', 'string', Rule::in(Animal::GENDERS)],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'age' => ['nullable', 'numeric', 'min:0'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'color_markings' => ['nullable', 'string', 'max:255'],
            'acquisition_type' => ['nullable', 'string', 'max:64', Rule::in(Animal::acquisitionTypesForValidation())],
            'acquisition_date' => ['nullable', 'date', 'before_or_equal:today'],
            'source' => ['nullable', 'string', 'max:255'],
            'mother_tag' => ['nullable', 'string', 'max:80'],
            'father_tag' => ['nullable', 'string', 'max:80'],
            'health_status' => ['required', 'string', Rule::in(Animal::HEALTH_STATUSES)],
            'production_status' => ['nullable', 'string', Rule::in(Animal::PRODUCTION_STATUSES)],
            'lifecycle_status' => ['required', 'string', Rule::in(Animal::LIFECYCLE_STATUSES)],
            'current_condition' => [
                'nullable',
                'string',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value === null || $value === '') {
                        return;
                    }
                    if (in_array($value, Animal::CURRENT_CONDITIONS, true)) {
                        return;
                    }
                    $animal = $this->route('animal');
                    if ($animal instanceof Animal && (string) ($animal->getOriginal('current_condition') ?? '') === (string) $value) {
                        return;
                    }
                    $fail(__('Select a valid current condition.'));
                },
            ],
            'photo' => ['nullable', 'image', 'max:4096'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
