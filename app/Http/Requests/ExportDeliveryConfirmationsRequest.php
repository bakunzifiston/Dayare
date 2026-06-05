<?php

namespace App\Http\Requests;

use App\Models\DeliveryConfirmation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExportDeliveryConfirmationsRequest extends FormRequest
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
            'format' => ['nullable', 'string', Rule::in(['csv', 'excel', 'pdf', 'json'])],
            'confirmation_status' => ['nullable', 'string', Rule::in(DeliveryConfirmation::STATUSES)],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'receiving_facility_id' => ['nullable', 'integer'],
            'client_id' => ['nullable', 'integer'],
        ];
    }
}
