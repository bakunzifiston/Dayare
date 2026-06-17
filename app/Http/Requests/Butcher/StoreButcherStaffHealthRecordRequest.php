<?php

namespace App\Http\Requests\Butcher;

use App\Http\Requests\Butcher\Concerns\ResolvesButcherBusiness;
use App\Models\ButcherStaffHealthRecord;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreButcherStaffHealthRecordRequest extends FormRequest
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
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'medical_card_number' => ['required', 'string', 'max:80'],
            'issued_date' => ['required', 'date'],
            'expiry_date' => ['required', 'date', 'after:issued_date'],
            'health_status' => ['required', 'string', Rule::in(ButcherStaffHealthRecord::HEALTH_STATUSES)],
            'last_checked_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
