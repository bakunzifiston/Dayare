<?php

namespace App\Http\Requests\Logistics;

use App\Models\LogisticsInvoice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $items = collect($this->input('items', []))
            ->filter(fn ($row): bool => is_array($row) && trim((string) ($row['description'] ?? '')) !== '')
            ->values()
            ->all();
        $this->merge(['items' => $items]);
    }

    public function rules(): array
    {
        return [
            'client_id' => ['required', 'integer', Rule::exists('clients', 'id')],
            'currency' => ['required', 'string', 'max:8'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'issued_at' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'payment_status' => ['nullable', Rule::in(LogisticsInvoice::PAYMENT_STATUSES)],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'min:0'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.total' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
