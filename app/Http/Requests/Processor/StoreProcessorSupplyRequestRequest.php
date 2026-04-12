<?php

namespace App\Http\Requests\Processor;

use App\Models\Business;
use App\Models\Facility;
use App\Support\FarmerAnimalType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProcessorSupplyRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $processorIds = $this->user()->accessibleProcessorBusinessIds()->all();

        return [
            'processor_business_id' => ['required', 'integer', Rule::in($processorIds)],
            'farmer_id' => [
                'required',
                'integer',
                Rule::exists('businesses', 'id')->where(fn ($q) => $q->where('type', Business::TYPE_FARMER)->where('status', Business::STATUS_ACTIVE)),
            ],
            'destination_facility_id' => ['required', 'integer', 'exists:facilities,id'],
            'animal_type' => ['required', 'string', Rule::in(FarmerAnimalType::ALL)],
            'quantity_requested' => ['required', 'integer', 'min:1'],
            'preferred_date' => ['nullable', 'date'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $data = $validator->getData();
            $fid = isset($data['destination_facility_id']) ? (int) $data['destination_facility_id'] : 0;
            $pid = isset($data['processor_business_id']) ? (int) $data['processor_business_id'] : 0;
            if ($fid && $pid) {
                $facility = Facility::query()->find($fid);
                if (! $facility || (int) $facility->business_id !== $pid) {
                    $validator->errors()->add('destination_facility_id', __('The facility must belong to the selected processor business.'));
                }
            }
        });
    }
}
