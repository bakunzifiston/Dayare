<?php

namespace App\Http\Requests;

use App\Models\Contract;
use Illuminate\Foundation\Http\FormRequest;

class UpdateContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $category = $this->input('contract_category', $this->route('contract')?->contract_category ?? 'supplier');
        $employeeTypes = implode(',', array_keys(Contract::EMPLOYEE_TYPES));
        $supplierTypes = implode(',', array_keys(Contract::SUPPLIER_TYPES));

        $rules = [
            'business_id' => ['required', 'exists:businesses,id'],
            'contract_category' => ['required', 'string', 'in:employee,supplier'],
            'contract_number' => ['required', 'string', 'max:100'],
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', "in:{$employeeTypes},{$supplierTypes}"],
            'description' => ['nullable', 'string'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['required', 'string', 'in:'.implode(',', array_keys(Contract::STATUSES))],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'renewal_date' => ['nullable', 'date'],
            'termination_reason' => ['nullable', 'string', 'max:255'],
            'contract_owner_id' => ['nullable', 'exists:users,id'],
        ];

        if ($category === 'employee') {
            $rules['employee_id'] = ['required', 'exists:employees,id'];
            $rules['facility_id'] = ['nullable', 'exists:facilities,id'];
            $rules['supplier_id'] = ['nullable', 'exists:suppliers,id'];
            $rules['job_position'] = ['nullable', 'string', 'max:255'];
            $rules['department'] = ['nullable', 'string', 'max:255'];
            $rules['supervisor_name'] = ['nullable', 'string', 'max:255'];
            $rules['employment_type'] = ['nullable', 'string', 'in:'.implode(',', array_keys(Contract::EMPLOYMENT_TYPES))];
            $rules['work_schedule'] = ['nullable', 'string'];
            $rules['salary_payment_terms'] = ['nullable', 'string'];
            $rules['working_hours'] = ['nullable', 'string'];
            $rules['probation_period'] = ['nullable', 'string'];
            $rules['medical_certificate_number'] = ['nullable', 'string', 'max:255'];
            $rules['medical_certificate_expiry_date'] = ['nullable', 'date'];
            $rules['safety_training_date'] = ['nullable', 'date'];
            $rules['certification_requirements'] = ['nullable', 'string'];
            $rules['signed_contract_file'] = ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:10240'];
            $rules['supporting_documents'] = ['nullable', 'array'];
            $rules['supporting_documents.*'] = ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:10240'];
        } else {
            $rules['supplier_id'] = ['required', 'exists:suppliers,id'];
            $rules['employee_id'] = ['nullable', 'exists:employees,id'];
            $rules['facility_id'] = ['nullable', 'exists:facilities,id'];
            $rules['farm_name'] = ['nullable', 'string', 'max:255'];
            $rules['farm_registration_number'] = ['nullable', 'string', 'max:255'];
            $rules['supplier_contact_person'] = ['nullable', 'string', 'max:255'];
            $rules['supplier_phone'] = ['nullable', 'string', 'max:100'];
            $rules['supplier_email'] = ['nullable', 'email'];
            $rules['location_district'] = ['nullable', 'string', 'max:255'];
            $rules['location_sector'] = ['nullable', 'string', 'max:255'];
            $rules['species_covered'] = ['nullable', 'string', 'max:255'];
            $rules['estimated_quantity'] = ['nullable', 'integer', 'min:0'];
            $rules['delivery_frequency'] = ['nullable', 'string', 'in:'.implode(',', array_keys(Contract::DELIVERY_FREQUENCIES))];
            $rules['animal_health_cert_requirement'] = ['nullable', 'string'];
            $rules['veterinary_inspection_requirement'] = ['nullable', 'string'];
            $rules['animal_welfare_compliance'] = ['nullable', 'string'];
            $rules['transport_responsibility'] = ['nullable', 'string', 'in:'.implode(',', array_keys(Contract::TRANSPORT_RESPONSIBILITY))];
            $rules['vehicle_plate'] = ['nullable', 'string', 'max:100'];
            $rules['driver_name'] = ['nullable', 'string', 'max:255'];
            $rules['signed_contract_file'] = ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:10240'];
            $rules['supporting_documents'] = ['nullable', 'array'];
            $rules['supporting_documents.*'] = ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:10240'];
        }

        return $rules;
    }
}
