<?php

declare(strict_types=1);

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'AnimalIntakeUpdateRequest',
    description: 'Aligned with UpdateAnimalIntakeRequest. `supplier_firstname` and `supplier_lastname` are conditionally required when `supplier_id` is null.',
    required: ['facility_id', 'intake_date', 'species', 'number_of_animals', 'status'],
    properties: [
        new OA\Property(property: 'facility_id', type: 'integer', example: 3),
        new OA\Property(property: 'supplier_id', type: 'integer', nullable: true, example: 12),
        new OA\Property(property: 'contract_id', type: 'integer', nullable: true, example: 41),
        new OA\Property(property: 'intake_date', type: 'string', format: 'date'),
        new OA\Property(property: 'supplier_firstname', type: 'string', nullable: true, maxLength: 255),
        new OA\Property(property: 'supplier_lastname', type: 'string', nullable: true, maxLength: 255),
        new OA\Property(property: 'supplier_contact', type: 'string', nullable: true, maxLength: 100),
        new OA\Property(property: 'farm_name', type: 'string', nullable: true, maxLength: 255),
        new OA\Property(property: 'farm_registration_number', type: 'string', nullable: true, maxLength: 100),
        new OA\Property(property: 'country_id', type: 'integer', nullable: true),
        new OA\Property(property: 'province_id', type: 'integer', nullable: true),
        new OA\Property(property: 'district_id', type: 'integer', nullable: true),
        new OA\Property(property: 'sector_id', type: 'integer', nullable: true),
        new OA\Property(property: 'cell_id', type: 'integer', nullable: true),
        new OA\Property(property: 'village_id', type: 'integer', nullable: true),
        new OA\Property(property: 'species', type: 'string', maxLength: 50),
        new OA\Property(property: 'number_of_animals', type: 'integer', minimum: 1),
        new OA\Property(property: 'unit_price', type: 'number', format: 'float', nullable: true, minimum: 0),
        new OA\Property(property: 'total_price', type: 'number', format: 'float', nullable: true, minimum: 0),
        new OA\Property(property: 'animal_identification_numbers', type: 'string', nullable: true),
        new OA\Property(property: 'transport_vehicle_plate', type: 'string', nullable: true, maxLength: 50),
        new OA\Property(property: 'driver_name', type: 'string', nullable: true, maxLength: 255),
        new OA\Property(property: 'animal_health_certificate_number', type: 'string', nullable: true, maxLength: 100),
        new OA\Property(property: 'health_certificate_issue_date', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'health_certificate_expiry_date', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'status', type: 'string', enum: ['received', 'approved', 'rejected']),
    ],
    type: 'object',
)]
final class AnimalIntakeUpdateRequest {}
