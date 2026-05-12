<?php

namespace App\Http\Requests\Farmer\Concerns;

use App\Models\MovementPermit;
use App\Models\MovementPermitAnimal;
use App\Models\MovementVeterinaryApproval;
use Illuminate\Validation\Rule;

trait ValidatesMovementPermit
{
    /** @return array<string, mixed> */
    protected function movementPermitRules(): array
    {
        return [
            'source_farm_id' => ['required', 'integer', 'exists:farms,id'],
            'permit_type' => ['required', 'string', Rule::in(MovementPermit::TYPES)],
            'movement_reason' => ['nullable', 'string', 'max:255'],
            'origin_location' => ['nullable', 'string', 'max:255'],
            'destination_location' => ['nullable', 'string', 'max:255'],
            'destination_district_id' => ['nullable', 'integer', 'exists:administrative_divisions,id'],
            'destination_sector_id' => ['nullable', 'integer', 'exists:administrative_divisions,id'],
            'destination_cell_id' => ['nullable', 'integer', 'exists:administrative_divisions,id'],
            'destination_village_id' => ['nullable', 'integer', 'exists:administrative_divisions,id'],
            'departure_date' => ['required', 'date'],
            'expected_arrival_date' => ['required', 'date', 'after_or_equal:departure_date'],
            'issued_by' => ['required', 'string', 'max:150'],
            'permit_status' => ['nullable', 'string', Rule::in(MovementPermit::STATUSES)],
            'notes' => ['nullable', 'string', 'max:5000'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.animal_id' => ['nullable', 'integer', 'exists:animals,id'],
            'lines.*.livestock_id' => ['nullable', 'integer', 'exists:livestock,id'],
            'lines.*.animal_identifier' => ['nullable', 'string', 'max:120'],
            'lines.*.quantity' => ['nullable', 'integer', 'min:1'],
            'lines.*.movement_condition' => ['nullable', 'string', Rule::in(MovementPermitAnimal::CONDITIONS)],
            'transport.vehicle_type' => ['nullable', 'string', 'max:64'],
            'transport.vehicle_number' => ['nullable', 'string', 'max:50'],
            'transport.trailer_number' => ['nullable', 'string', 'max:50'],
            'transport.driver_name' => ['nullable', 'string', 'max:255'],
            'transport.driver_phone' => ['nullable', 'string', 'max:50'],
            'transport.transporter_company' => ['nullable', 'string', 'max:255'],
            'transport.route_information' => ['nullable', 'string', 'max:2000'],
            'transport.emergency_contact' => ['nullable', 'string', 'max:255'],
            'transport.transport_notes' => ['nullable', 'string', 'max:2000'],
            'veterinary.veterinarian_name' => ['nullable', 'string', 'max:255'],
            'veterinary.inspection_date' => ['nullable', 'date'],
            'veterinary.inspection_result' => ['nullable', 'string', Rule::in(MovementVeterinaryApproval::RESULTS)],
            'veterinary.health_clearance' => ['nullable', 'boolean'],
            'veterinary.disease_check' => ['nullable', 'boolean'],
            'veterinary.quarantine_check' => ['nullable', 'boolean'],
            'veterinary.recommendations' => ['nullable', 'string', 'max:2000'],
            'veterinary.approval_status' => ['nullable', 'string', Rule::in(MovementVeterinaryApproval::APPROVAL_STATUSES)],
            'veterinary.notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
