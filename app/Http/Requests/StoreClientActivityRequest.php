<?php

namespace App\Http\Requests;

use App\Models\ClientActivity;
use Illuminate\Foundation\Http\FormRequest;

class StoreClientActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'activity_type' => ['required', 'string', 'in:'.implode(',', array_keys(ClientActivity::TYPES))],
            'subject' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'occurred_at' => ['required', 'date'],
        ];
    }
}
