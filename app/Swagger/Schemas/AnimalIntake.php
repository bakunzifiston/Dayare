<?php

declare(strict_types=1);

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'AnimalIntake',
    description: 'Animal origin / intake before slaughter. FK: facility_id. Optional supply_request_id, farm_id, contract_id.',
    required: ['id', 'facility_id', 'intake_date', 'species', 'number_of_animals', 'status'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 100),
        new OA\Property(property: 'facility_id', type: 'integer', example: 3),
        new OA\Property(property: 'supply_request_id', type: 'integer', nullable: true),
        new OA\Property(property: 'farm_id', type: 'integer', nullable: true),
        new OA\Property(property: 'intake_date', type: 'string', format: 'date'),
        new OA\Property(property: 'supplier_firstname', type: 'string', example: 'Paul'),
        new OA\Property(property: 'supplier_lastname', type: 'string', example: 'Farmer'),
        new OA\Property(property: 'species', type: 'string', example: 'Cattle'),
        new OA\Property(property: 'number_of_animals', type: 'integer', example: 20),
        new OA\Property(property: 'status', type: 'string', enum: ['received', 'approved', 'rejected']),
        new OA\Property(property: 'health_certificate_expiry_date', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'animal_health_certificate_number', type: 'string', nullable: true),
    ],
    type: 'object',
)]
final class AnimalIntake {}
