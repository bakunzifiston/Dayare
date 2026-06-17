<?php

namespace App\Http\Requests\Butcher;

use App\Rules\RwandaDistrictName;
use Illuminate\Foundation\Http\FormRequest;

class StoreButcherOutletRequest extends FormRequest
{
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
            'name' => ['required', 'string', 'max:255'],
            'district' => ['required', 'string', 'max:120', new RwandaDistrictName],
            'sector' => ['nullable', 'string', 'max:120'],
            'phone' => ['required', 'string', 'regex:/^\+2507[0-9]{8}$/'],
            'gps_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'gps_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'is_primary' => ['nullable', 'boolean'],
        ];
    }
}
