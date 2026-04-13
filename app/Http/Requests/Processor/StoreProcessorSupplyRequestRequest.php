<?php

namespace App\Http\Requests\Processor;

use App\Models\FarmerHealthCertificate;
use App\Models\Facility;
use App\Models\Livestock;
use App\Models\SupplyRequest;
use Carbon\Carbon;
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
            'destination_facility_id' => ['required', 'integer', 'exists:facilities,id'],
            'requested_livestock_id' => ['required', 'integer', 'exists:livestock,id'],
            'quantity_requested' => ['required', 'integer', 'min:1'],
            'required_breed' => ['nullable', 'string', 'max:120'],
            'required_weight' => ['nullable', 'string', 'max:120'],
            'healthy_stock_required' => ['nullable', 'boolean'],
            'certification_required' => ['nullable', 'boolean'],
            'required_certification_type' => ['nullable', 'string', Rule::in(FarmerHealthCertificate::TYPES)],
            'preferred_date' => ['nullable', 'date'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'healthy_stock_required' => $this->boolean('healthy_stock_required', true),
            'certification_required' => $this->boolean('certification_required'),
            'required_breed' => $this->filled('required_breed') ? trim((string) $this->input('required_breed')) : null,
            'required_weight' => $this->filled('required_weight') ? trim((string) $this->input('required_weight')) : null,
            'required_certification_type' => $this->filled('required_certification_type') ? (string) $this->input('required_certification_type') : null,
        ]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $data = $validator->getData();
            $fid = isset($data['destination_facility_id']) ? (int) $data['destination_facility_id'] : 0;
            $pid = isset($data['processor_business_id']) ? (int) $data['processor_business_id'] : 0;
            $livestockId = isset($data['requested_livestock_id']) ? (int) $data['requested_livestock_id'] : 0;
            $qty = isset($data['quantity_requested']) ? (int) $data['quantity_requested'] : 0;

            if ($fid && $pid) {
                $facility = Facility::query()->find($fid);
                if (! $facility || (int) $facility->business_id !== $pid) {
                    $validator->errors()->add('destination_facility_id', __('The facility must belong to the selected processor business.'));
                }
            }

            if (! $livestockId || $qty <= 0) {
                return;
            }

            /** @var Livestock|null $livestock */
            $livestock = Livestock::query()->with('farm')->find($livestockId);
            if (! $livestock || ! $livestock->farm) {
                $validator->errors()->add('requested_livestock_id', __('Selected livestock is invalid.'));

                return;
            }

            $reserved = (int) SupplyRequest::query()
                ->where('requested_livestock_id', $livestock->id)
                ->whereIn('status', [SupplyRequest::STATUS_PENDING, SupplyRequest::STATUS_ACCEPTED])
                ->sum('quantity_requested');

            $available = max(0, (int) $livestock->healthy_quantity - $reserved);
            if ($qty > $available) {
                $validator->errors()->add('quantity_requested', __('Requested quantity exceeds available healthy stock.'));
            }

            if (! empty($data['healthy_stock_required']) && (int) $livestock->healthy_quantity < $qty) {
                $validator->errors()->add('quantity_requested', __('Requested quantity exceeds healthy stock.'));
            }

            if (! empty($data['required_breed']) && strcasecmp((string) $data['required_breed'], (string) $livestock->breed) !== 0) {
                $validator->errors()->add('required_breed', __('Breed requirement does not match selected livestock.'));
            }

            if (! empty($data['certification_required'])) {
                $requiredType = $data['required_certification_type'] ?? null;
                $validCertQuery = FarmerHealthCertificate::query()
                    ->where('farmer_id', (int) $livestock->farm->business_id)
                    ->where('farm_id', (int) $livestock->farm_id)
                    ->where('status', FarmerHealthCertificate::STATUS_VALID)
                    ->whereDate('issue_date', '<=', Carbon::today())
                    ->where(function ($query) {
                        $query->whereNull('expiry_date')
                            ->orWhereDate('expiry_date', '>=', Carbon::today());
                    })
                    ->where(function ($query) use ($livestock) {
                        $query->where('livestock_id', $livestock->id)
                            ->orWhereNull('livestock_id');
                    });

                if ($requiredType) {
                    $validCertQuery->where('certificate_type', $requiredType);
                }

                if (! $validCertQuery->exists()) {
                    $validator->errors()->add('required_certification_type', __('Required certification is missing or expired for this livestock.'));
                }
            }
        });
    }
}
