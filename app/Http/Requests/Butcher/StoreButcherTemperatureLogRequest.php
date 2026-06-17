<?php

namespace App\Http\Requests\Butcher;

use App\Http\Requests\Butcher\Concerns\ResolvesButcherBusiness;
use App\Models\ButcherDisposalLog;
use App\Models\ButcherInventoryBatch;
use App\Models\ButcherTemperatureLog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreButcherTemperatureLogRequest extends FormRequest
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
            'storage_location' => ['required', 'string', 'max:120'],
            'storage_type' => ['required', 'string', Rule::in(ButcherTemperatureLog::STORAGE_TYPES)],
            'temperature_celsius' => ['required', 'numeric', 'between:-50,50'],
            'logged_at' => ['nullable', 'date'],
            'breach_note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
