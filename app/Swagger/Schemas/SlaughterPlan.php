<?php

declare(strict_types=1);

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'SlaughterPlan',
    description: 'Slaughter session plan. FK: facility_id, animal_intake_id, inspector_id.',
    required: ['id', 'slaughter_date', 'facility_id', 'animal_intake_id', 'inspector_id', 'species', 'number_of_animals_scheduled', 'status'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 50),
        new OA\Property(property: 'slaughter_date', type: 'string', format: 'date'),
        new OA\Property(property: 'facility_id', type: 'integer', example: 3),
        new OA\Property(property: 'animal_intake_id', type: 'integer', example: 100),
        new OA\Property(property: 'inspector_id', type: 'integer', example: 5),
        new OA\Property(property: 'species', type: 'string', example: 'Cattle'),
        new OA\Property(property: 'number_of_animals_scheduled', type: 'integer', example: 10),
        new OA\Property(property: 'status', type: 'string', enum: ['planned', 'approved']),
    ],
    type: 'object',
)]
final class SlaughterPlan {}
