<?php

declare(strict_types=1);

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'SlaughterPlanUpdateRequest',
    description: 'Aligned with UpdateSlaughterPlanRequest. `animal_intake_id` is nullable on update for backward compatibility.',
    required: ['slaughter_date', 'facility_id', 'inspector_id', 'species', 'number_of_animals_scheduled', 'status'],
    properties: [
        new OA\Property(property: 'slaughter_date', type: 'string', format: 'date'),
        new OA\Property(property: 'facility_id', type: 'integer'),
        new OA\Property(property: 'animal_intake_id', type: 'integer', nullable: true),
        new OA\Property(property: 'inspector_id', type: 'integer'),
        new OA\Property(property: 'species', type: 'string', maxLength: 50),
        new OA\Property(property: 'number_of_animals_scheduled', type: 'integer', minimum: 1),
        new OA\Property(property: 'status', type: 'string', enum: ['planned', 'approved']),
    ],
    type: 'object',
)]
final class SlaughterPlanUpdateRequest {}
