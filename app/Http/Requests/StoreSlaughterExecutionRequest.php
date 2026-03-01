<?php

namespace App\Http\Requests;

use App\Models\SlaughterExecution;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSlaughterExecutionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'slaughter_plan_id' => ['required', 'exists:slaughter_plans,id'],
            'actual_animals_slaughtered' => ['required', 'integer', 'min:0'],
            'slaughter_time' => ['required', 'date'],
            'status' => ['required', 'string', Rule::in(SlaughterExecution::STATUSES)],
        ];
    }
}
