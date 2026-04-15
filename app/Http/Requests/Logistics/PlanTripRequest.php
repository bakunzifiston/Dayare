<?php

namespace App\Http\Requests\Logistics;

use App\Models\LogisticsComplianceDocument;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlanTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_id' => ['required', 'integer', Rule::exists('logistics_companies', 'id')],
            'vehicle_id' => ['required', 'integer', Rule::exists('logistics_vehicles', 'id')],
            'driver_id' => ['required', 'integer', Rule::exists('logistics_drivers', 'id')],
            'planned_departure' => ['required', 'date'],
            'planned_arrival' => ['required', 'date', 'after:planned_departure'],
            'orders' => ['required', 'array', 'min:1'],
            'orders.*.order_id' => ['required', 'integer', Rule::exists('logistics_orders', 'id')],
            'orders.*.allocated_quantity' => ['required', 'integer', 'min:1'],
            'compliance_documents' => ['nullable', 'array'],
            'compliance_documents.*.type' => ['required_with:compliance_documents', Rule::in(LogisticsComplianceDocument::TYPES)],
            'compliance_documents.*.reference_id' => ['nullable', 'integer'],
            'compliance_documents.*.status' => ['required_with:compliance_documents', Rule::in(LogisticsComplianceDocument::STATUSES)],
        ];
    }
}

