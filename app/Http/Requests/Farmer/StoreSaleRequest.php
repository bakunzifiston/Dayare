<?php

namespace App\Http\Requests\Farmer;

use App\Http\Requests\Farmer\Concerns\ValidatesSaleRegistration;
use App\Models\Buyer;
use App\Models\Farm;
use App\Models\MovementPermit;
use App\Models\Sale;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreSaleRequest extends FormRequest
{
    use ValidatesSaleRegistration;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return $this->saleRegistrationRules();
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $farm = Farm::query()->find((int) $this->input('farm_id'));
            $buyer = Buyer::query()->find((int) $this->input('buyer_id'));
            $farmerIds = $this->user()->accessibleFarmerBusinessIds();

            if ($farm && ! $farmerIds->contains((int) $farm->business_id)) {
                $validator->errors()->add('farm_id', __('Selected farm is not accessible.'));
            }

            if ($buyer && ! $farmerIds->contains((int) $buyer->business_id)) {
                $validator->errors()->add('buyer_id', __('Selected buyer is not accessible.'));
            }

            if ($buyer && $buyer->status === Buyer::STATUS_BLACKLISTED) {
                $validator->errors()->add('buyer_id', __('This buyer is blacklisted.'));
            }

            $saleType = (string) $this->input('sale_type');
            if (in_array($saleType, [Sale::TYPE_EXPORT, Sale::TYPE_MARKET], true) && ! $this->input('movement_permit_id')) {
                $validator->errors()->add('movement_permit_id', __('A movement permit is required for this sale type.'));
            }

            if ($permitId = $this->input('movement_permit_id')) {
                $permit = MovementPermit::query()->find((int) $permitId);
                if ($permit && $farm && (int) $permit->source_farm_id !== (int) $farm->id) {
                    $validator->errors()->add('movement_permit_id', __('Movement permit must belong to the selected farm.'));
                }
            }

            $lines = $this->input('lines', []);
            $hasAnimal = collect($lines)->contains(fn ($line) => ! empty($line['animal_id']));
            $hasLivestock = collect($lines)->contains(fn ($line) => ! empty($line['livestock_id']));

            if ($saleType === Sale::TYPE_INDIVIDUAL && ! $hasAnimal) {
                $validator->errors()->add('lines', __('Individual animal sales require at least one animal line.'));
            }

            if ($saleType === Sale::TYPE_GROUP && ! $hasLivestock && ! $hasAnimal) {
                $validator->errors()->add('lines', __('Group sales require livestock or animal lines.'));
            }
        });
    }
}
