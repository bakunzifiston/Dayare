<?php

namespace App\Http\Requests\Farmer;

use App\Http\Requests\Farmer\Concerns\ValidatesMovementPermit;
use App\Models\Farm;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreMovementPermitRequest extends FormRequest
{
    use ValidatesMovementPermit;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return $this->movementPermitRules();
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $farm = Farm::query()->find((int) $this->input('source_farm_id'));
            $farmerIds = $this->user()->accessibleFarmerBusinessIds();
            if ($farm && ! $farmerIds->contains((int) $farm->business_id)) {
                $validator->errors()->add('source_farm_id', __('Selected farm is not accessible.'));
            }

            $origin = trim((string) ($this->input('origin_location') ?: $farm?->name));
            $destination = trim((string) $this->input('destination_location'));
            if ($destination !== '' && strcasecmp($origin, $destination) === 0) {
                $validator->errors()->add('destination_location', __('Destination must differ from origin.'));
            }

            $hasAnimal = collect($this->input('lines', []))->contains(fn ($line) => ! empty($line['animal_id']) || ! empty($line['livestock_id']) || ! empty($line['animal_identifier']));
            if (! $hasAnimal) {
                $validator->errors()->add('lines', __('At least one animal or livestock line is required.'));
            }
        });
    }
}
