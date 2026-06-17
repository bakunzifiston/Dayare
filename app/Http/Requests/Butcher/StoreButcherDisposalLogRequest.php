<?php

namespace App\Http\Requests\Butcher;

use App\Http\Requests\Butcher\Concerns\ResolvesButcherBusiness;
use App\Models\ButcherDisposalLog;
use App\Models\ButcherInventoryBatch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreButcherDisposalLogRequest extends FormRequest
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
        $batch = $this->route('batch');

        return [
            'batch_id' => [
                'required',
                'integer',
                Rule::exists('butcher_inventory_batches', 'id')->where(function ($query) use ($business) {
                    $query->where('business_id', $business->id)
                        ->whereIn('status', [
                            ButcherInventoryBatch::STATUS_IN_STORAGE,
                            ButcherInventoryBatch::STATUS_PARTIALLY_USED,
                            ButcherInventoryBatch::STATUS_EXPIRED,
                        ]);
                }),
            ],
            'weight_disposed_kg' => ['required', 'numeric', 'min:0.1'],
            'reason' => ['required', 'string', Rule::in(ButcherDisposalLog::REASONS)],
            'disposed_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $batch = $this->route('batch');
        if ($batch instanceof ButcherInventoryBatch) {
            $this->merge(['batch_id' => $batch->id]);
        }
    }
}
