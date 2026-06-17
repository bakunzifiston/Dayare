<?php

namespace App\Http\Requests\Butcher;

use App\Http\Requests\Butcher\Concerns\ResolvesButcherBusiness;
use App\Models\ButcherSanitationRecord;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreButcherSanitationRecordRequest extends FormRequest
{
    use ResolvesButcherBusiness;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $business = $this->butcherBusiness();

        return [
            'outlet_id' => [
                'required',
                'integer',
                Rule::exists('butcher_outlets', 'id')->where('business_id', $business->id),
            ],
            'equipment_name' => ['required', 'string', 'max:160'],
            'cleaning_type' => ['required', 'string', Rule::in(ButcherSanitationRecord::CLEANING_TYPES)],
            'chemical_used' => ['nullable', 'string', 'max:160'],
            'performed_at' => ['nullable', 'date'],
            'next_due_at' => ['nullable', 'date', 'after_or_equal:performed_at'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
