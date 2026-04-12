<?php

namespace App\Http\Requests\Farmer;

use App\Models\Farm;
use App\Models\Livestock;
use App\Support\FarmerAnimalType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLivestockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'breed' => $this->input('breed') !== null ? trim((string) $this->input('breed')) : '',
        ]);
        foreach (['feeding_type', 'health_status'] as $key) {
            if ($this->input($key) === '') {
                $this->merge([$key => null]);
            }
        }
        if ($this->input('base_price') === '' || $this->input('base_price') === null) {
            $this->merge(['base_price' => null]);
        }
    }

    public function rules(): array
    {
        /** @var Farm $farm */
        $farm = $this->route('farm');
        /** @var Livestock $livestock */
        $livestock = $this->route('livestock');

        return [
            'type' => [
                'required',
                'string',
                Rule::in(FarmerAnimalType::ALL),
                Rule::unique('livestock', 'type')
                    ->ignore($livestock->id)
                    ->where(fn ($q) => $q
                        ->where('farm_id', $farm->id)
                        ->where('breed', $this->input('breed') ?? '')),
            ],
            'breed' => ['nullable', 'string', 'max:120'],
            'feeding_type' => ['nullable', 'string', Rule::in(Livestock::FEEDING_TYPES)],
            'total_quantity' => ['required', 'integer', 'min:0'],
            'available_quantity' => ['required', 'integer', 'min:0', 'lte:total_quantity'],
            'base_price' => ['nullable', 'numeric', 'min:0'],
            'health_status' => ['nullable', 'string', Rule::in(Livestock::HEALTH_STATUSES)],
        ];
    }
}
