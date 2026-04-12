<?php

declare(strict_types=1);

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'SlaughterPlanCreateRequest',
    description: 'Aligned with StoreSlaughterPlanRequest: slaughter_date after_or_equal today; inspector must exist and belong to facility_id; species must match active species name; animal_intake must match facility, species, non-expired health cert, and scheduled count ≤ remaining animals on intake.',
    required: ['slaughter_date', 'facility_id', 'animal_intake_id', 'inspector_id', 'species', 'number_of_animals_scheduled', 'status'],
    properties: [
        new OA\Property(property: 'slaughter_date', type: 'string', format: 'date'),
        new OA\Property(property: 'facility_id', type: 'integer'),
        new OA\Property(property: 'animal_intake_id', type: 'integer'),
        new OA\Property(property: 'inspector_id', type: 'integer'),
        new OA\Property(property: 'species', type: 'string'),
        new OA\Property(property: 'number_of_animals_scheduled', type: 'integer', minimum: 1),
        new OA\Property(property: 'status', type: 'string', enum: ['planned', 'approved']),
    ],
    type: 'object',
)]
final class SlaughterPlanCreateRequest {}
