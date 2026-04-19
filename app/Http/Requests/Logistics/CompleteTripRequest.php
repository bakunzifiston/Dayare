<?php

namespace App\Http\Requests\Logistics;

use App\Models\LogisticsTrip;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompleteTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('status') === LogisticsTrip::STATUS_CANCELLED) {
            $this->merge([
                'delivered_weight_kg' => 0,
                'loss_weight_kg' => 0,
            ]);

            return;
        }

        if ($this->input('delivered_weight_kg') === null || $this->input('delivered_weight_kg') === '') {
            $this->merge(['delivered_weight_kg' => 0]);
        }
        if ($this->input('loss_weight_kg') === null || $this->input('loss_weight_kg') === '') {
            $this->merge(['loss_weight_kg' => 0]);
        }
    }

    public function rules(): array
    {
        return [
            'actual_arrival' => ['nullable', 'date'],
            'status' => ['required', Rule::in([LogisticsTrip::STATUS_COMPLETED, LogisticsTrip::STATUS_CANCELLED])],
            'delivered_weight_kg' => ['required_if:status,'.LogisticsTrip::STATUS_COMPLETED, 'integer', 'min:0'],
            'loss_weight_kg' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
