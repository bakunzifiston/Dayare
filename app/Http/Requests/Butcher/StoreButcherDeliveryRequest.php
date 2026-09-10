<?php

namespace App\Http\Requests\Butcher;

use App\Http\Requests\Butcher\Concerns\ResolvesButcherBusiness;
use App\Http\Requests\Butcher\Concerns\ValidatesButcherSupplierOwnership;
use App\Models\ButcherDelivery;
use App\Models\ButcherDeliveryLine;
use App\Models\ButcherPurchaseOrder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreButcherDeliveryRequest extends FormRequest
{
    use ResolvesButcherBusiness;
    use ValidatesButcherSupplierOwnership;

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
            'purchase_order_id' => [
                'nullable',
                'integer',
                Rule::exists('butcher_purchase_orders', 'id')->where('business_id', $business->id),
            ],
            'supplier_id' => $this->activeSupplierRule((int) $business->id),
            'outlet_id' => [
                'required',
                'integer',
                Rule::exists('butcher_outlets', 'id')->where('business_id', $business->id),
            ],
            'received_at' => ['nullable', 'date'],
            'certificate_ref' => ['nullable', 'string', 'max:100'],
            'certificate_issuer' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'storage_location' => ['nullable', 'string', 'max:255'],

            // Line-item receiving (preferred).
            'lines' => ['required_without_all:meat_type,condition,received_weight_kg', 'array', 'min:1'],
            'lines.*.meat_type' => ['required_with:lines', 'string', Rule::in(ButcherDelivery::MEAT_TYPES)],
            'lines.*.expected_weight_kg' => ['nullable', 'numeric', 'min:0'],
            'lines.*.received_weight_kg' => ['required_with:lines', 'numeric', 'min:0.1'],
            'lines.*.temperature_c' => ['nullable', 'numeric', 'between:-50,50'],
            'lines.*.condition_notes' => ['nullable', 'string', 'max:2000'],
            'lines.*.outcome' => ['required_with:lines', 'string', Rule::in(ButcherDeliveryLine::OUTCOMES)],
            'lines.*.accepted_weight_kg' => ['nullable', 'numeric', 'min:0'],
            'lines.*.rejected_weight_kg' => ['nullable', 'numeric', 'min:0'],
            'lines.*.unit_cost' => ['required_with:lines', 'numeric', 'min:0'],

            // Legacy single-condition payload (still accepted for BC / older clients).
            'meat_type' => ['required_without:lines', 'string', Rule::in(ButcherDelivery::MEAT_TYPES)],
            'received_weight_kg' => ['required_without:lines', 'numeric', 'min:0.1'],
            'unit_cost_per_kg' => ['required_without:lines', 'numeric', 'min:0'],
            'condition' => ['required_without:lines', 'string', Rule::in(ButcherDelivery::CONDITIONS)],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $lines = $this->input('lines');
            if (! is_array($lines)) {
                return;
            }

            foreach ($lines as $index => $line) {
                if (! is_array($line)) {
                    continue;
                }

                $outcome = (string) ($line['outcome'] ?? '');
                $received = round((float) ($line['received_weight_kg'] ?? 0), 3);
                $accepted = array_key_exists('accepted_weight_kg', $line)
                    ? round((float) $line['accepted_weight_kg'], 3)
                    : null;
                $rejected = array_key_exists('rejected_weight_kg', $line)
                    ? round((float) $line['rejected_weight_kg'], 3)
                    : null;

                if ($accepted === null || $rejected === null) {
                    continue;
                }

                if (abs(($accepted + $rejected) - $received) > 0.001) {
                    $validator->errors()->add(
                        "lines.$index.accepted_weight_kg",
                        __('Accepted + rejected weight must equal received weight for each line.')
                    );
                }

                if ($outcome === ButcherDeliveryLine::OUTCOME_PARTIALLY_ACCEPTED
                    && ($accepted <= 0 || $rejected <= 0)) {
                    $validator->errors()->add(
                        "lines.$index.outcome",
                        __('Partially accepted lines need both accepted and rejected weight.')
                    );
                }
            }
        });
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('purchase_order_id')) {
            $order = ButcherPurchaseOrder::query()->find($this->input('purchase_order_id'));
            if ($order !== null && ! $this->filled('supplier_id')) {
                $this->merge(['supplier_id' => $order->supplier_id]);
            }
        }

        $lines = $this->input('lines');
        if (! is_array($lines)) {
            return;
        }

        $normalized = [];
        foreach ($lines as $line) {
            if (! is_array($line)) {
                continue;
            }
            if (isset($line['unit_cost_per_kg']) && ! isset($line['unit_cost'])) {
                $line['unit_cost'] = $line['unit_cost_per_kg'];
            }
            $normalized[] = $line;
        }

        $this->merge(['lines' => $normalized]);
    }
}
