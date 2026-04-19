<?php

namespace App\Http\Requests\Logistics;

use App\Models\LogisticsTrackingLog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreTrackingLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $lat = $this->input('latitude');
        $lon = $this->input('longitude');
        if ($lat === '' || $lat === null) {
            $this->merge(['latitude' => null]);
        }
        if ($lon === '' || $lon === null) {
            $this->merge(['longitude' => null]);
        }
        if ($this->input('location_id') === '') {
            $this->merge(['location_id' => null]);
        }
    }

    public function rules(): array
    {
        return [
            'event_time' => ['required', 'date'],
            'location_id' => ['nullable', 'integer', Rule::exists('locations', 'id')],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'status' => ['required', Rule::in(LogisticsTrackingLog::STATUSES)],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $loc = $this->input('location_id');
            $lat = $this->input('latitude');
            $lon = $this->input('longitude');
            $hasPoint = $lat !== null && $lat !== '' && $lon !== null && $lon !== '';
            if (! $loc && ! $hasPoint) {
                $validator->errors()->add(
                    'location_id',
                    __('Provide a saved location or both latitude and longitude.')
                );
            }
        });
    }
}
