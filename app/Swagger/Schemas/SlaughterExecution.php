<?php

declare(strict_types=1);

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'SlaughterExecution',
    description: 'Recorded slaughter event for a plan. FK: slaughter_plan_id.',
    required: ['id', 'slaughter_plan_id', 'actual_animals_slaughtered', 'slaughter_time', 'status'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 70),
        new OA\Property(property: 'slaughter_plan_id', type: 'integer', example: 50),
        new OA\Property(property: 'actual_animals_slaughtered', type: 'integer', example: 10),
        new OA\Property(property: 'slaughter_time', type: 'string', format: 'date-time'),
        new OA\Property(property: 'status', type: 'string', enum: ['scheduled', 'in_progress', 'completed', 'cancelled']),
    ],
    type: 'object',
)]
final class SlaughterExecution {}
