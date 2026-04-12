<?php

declare(strict_types=1);

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'AnimalIntakeCreateRequest',
    description: 'Mobile create body. Web UI supports additional optional fields (divisions, supplier_id, health cert dates) via StoreAnimalIntakeRequest.',
    required: ['facility_id', 'intake_date', 'species', 'number_of_animals', 'status', 'supplier_firstname', 'supplier_lastname'],
    properties: [
        new OA\Property(property: 'facility_id', type: 'integer', example: 3),
        new OA\Property(property: 'intake_date', type: 'string', format: 'date'),
        new OA\Property(property: 'species', type: 'string', maxLength: 50),
        new OA\Property(property: 'number_of_animals', type: 'integer', minimum: 1),
        new OA\Property(property: 'status', type: 'string', enum: ['received', 'approved', 'rejected']),
        new OA\Property(property: 'supplier_firstname', type: 'string', maxLength: 100),
        new OA\Property(property: 'supplier_lastname', type: 'string', maxLength: 100),
        new OA\Property(property: 'supplier_contact', type: 'string', nullable: true, maxLength: 100),
        new OA\Property(property: 'farm_name', type: 'string', nullable: true),
        new OA\Property(property: 'animal_identification_numbers', type: 'string', nullable: true),
        new OA\Property(property: 'notes', type: 'string', nullable: true),
    ],
    type: 'object',
)]
final class AnimalIntakeCreateRequest {}
