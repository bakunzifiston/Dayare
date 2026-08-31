<?php

namespace App\Http\Requests\Finance;

use App\Models\FinanceEbmRecord;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveFinanceEbmRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $businessId = $user?->activeProcessorBusinessId();
        if ($user === null || $businessId === null) {
            return false;
        }

        $ebm = $this->route('ebm');
        if ($ebm instanceof FinanceEbmRecord) {
            return (int) $ebm->business_id === $businessId;
        }

        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'ebm_invoice_number' => $this->filled('ebm_invoice_number')
                ? trim((string) $this->input('ebm_invoice_number'))
                : null,
            'ebm_receipt_number' => $this->filled('ebm_receipt_number')
                ? trim((string) $this->input('ebm_receipt_number'))
                : null,
            'notes' => $this->filled('notes') ? trim((string) $this->input('notes')) : null,
            'finance_invoice_id' => $this->filled('finance_invoice_id') ? $this->input('finance_invoice_id') : null,
            'facility_id' => $this->filled('facility_id') ? $this->input('facility_id') : null,
            'amount' => $this->filled('amount') ? $this->input('amount') : null,
            'issued_at' => $this->filled('issued_at') ? $this->input('issued_at') : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $businessId = (int) $this->user()->activeProcessorBusinessId();
        $recordId = $this->route('ebm') instanceof FinanceEbmRecord
            ? (int) $this->route('ebm')->id
            : null;

        $uniqueNumber = Rule::unique('finance_ebm_records', 'ebm_invoice_number')
            ->where(fn ($query) => $query->where('business_id', $businessId));
        $uniqueInvoice = Rule::unique('finance_ebm_records', 'finance_invoice_id')
            ->where(fn ($query) => $query->where('business_id', $businessId));

        if ($recordId !== null) {
            $uniqueNumber = $uniqueNumber->ignore($recordId);
            $uniqueInvoice = $uniqueInvoice->ignore($recordId);
        }

        return [
            'finance_invoice_id' => [
                'nullable',
                'integer',
                Rule::exists('finance_invoices', 'id')->where(
                    fn ($query) => $query->where('business_id', $businessId)
                ),
                $uniqueInvoice,
            ],
            'facility_id' => [
                'nullable',
                'integer',
                Rule::exists('facilities', 'id')->where(
                    fn ($query) => $query->where('business_id', $businessId)
                ),
            ],
            'ebm_invoice_number' => ['required', 'string', 'max:80', $uniqueNumber],
            'ebm_receipt_number' => ['nullable', 'string', 'max:80'],
            'issued_at' => ['nullable', 'date'],
            'amount' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'status' => ['required', Rule::in(FinanceEbmRecord::STATUSES)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ebm_invoice_number.required' => __('Enter the EBM invoice or reference number.'),
            'ebm_invoice_number.unique' => __('This EBM invoice number is already recorded for this business.'),
            'ebm_invoice_number.max' => __('The EBM invoice number may not be longer than 80 characters.'),
            'finance_invoice_id.exists' => __('Select an AR invoice that belongs to this business.'),
            'finance_invoice_id.unique' => __('This invoice already has an EBM record.'),
            'facility_id.exists' => __('Select a site that belongs to this business.'),
            'amount.min' => __('EBM amount cannot be negative.'),
            'amount.max' => __('EBM amount is too large.'),
            'status.required' => __('Select an EBM status.'),
            'status.in' => __('Select a valid EBM status.'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'finance_invoice_id' => __('linked AR invoice'),
            'facility_id' => __('site'),
            'ebm_invoice_number' => __('EBM invoice number'),
            'ebm_receipt_number' => __('EBM receipt number'),
            'issued_at' => __('EBM issue date'),
            'amount' => __('EBM amount'),
            'status' => __('EBM status'),
            'notes' => __('notes'),
        ];
    }
}
