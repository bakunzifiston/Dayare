<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesSlaughterExecutionRules;
use App\Models\SlaughterExecution;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSlaughterExecutionRequest extends FormRequest
{
    use ValidatesSlaughterExecutionRules;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->prepareSlaughterExecutionValidation();
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'slaughter_plan_id' => ['sometimes', 'required', 'exists:slaughter_plans,id'],
            'actual_animals_slaughtered' => ['required', 'integer', 'min:0'],
            'slaughter_time' => ['required', 'date'],
            'status' => ['required', 'string', Rule::in(SlaughterExecution::STATUSES)],
            'item_slaughters' => ['nullable', 'array'],
            'item_slaughters.*.animal_intake_item_id' => ['required', 'integer', 'exists:animal_intake_items,id'],
            'item_slaughters.*.meat_quantity_kg' => ['required', 'numeric', 'min:0.1', 'max:9999'],
            'item_slaughters.*.notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $this->validateSlaughterExecutionBusinessRules($validator);
        });
    }
}
