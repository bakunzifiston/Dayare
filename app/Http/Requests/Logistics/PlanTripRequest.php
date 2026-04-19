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
            'order_id' => ['required', 'integer', Rule::exists('logistics_orders', 'id')],
            'origin_location_id' => ['required', 'integer', Rule::exists('locations', 'id')],
            'destination_location_id' => ['required', 'integer', Rule::exists('locations', 'id'), 'different:origin_location_id'],
            'vehicle_id' => ['required', 'integer', Rule::exists('logistics_vehicles', 'id')],
            'driver_id' => ['required', 'integer', Rule::exists('logistics_drivers', 'id')],
            'planned_departure' => ['required', 'date'],
            'planned_arrival' => ['required', 'date', 'after:planned_departure'],
            'allocated_weight_kg' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'compliance_documents' => ['nullable', 'array'],
            'compliance_documents.*.type' => ['required_with:compliance_documents', Rule::in(LogisticsComplianceDocument::TYPES)],
            'compliance_documents.*.reference_id' => ['nullable', 'integer'],
            'compliance_documents.*.status' => ['required_with:compliance_documents', Rule::in(LogisticsComplianceDocument::STATUSES)],
        ];
    }
}
