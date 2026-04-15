<?php

namespace App\Http\Requests\Logistics;

use App\Models\LogisticsTrackingLog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTrackingLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'timestamp' => ['required', 'date'],
            'location' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in(LogisticsTrackingLog::STATUSES)],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}

