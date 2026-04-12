<?php

declare(strict_types=1);

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'SlaughterExecutionCreateRequest',
    required: ['slaughter_plan_id', 'actual_animals_slaughtered', 'slaughter_time', 'status'],
    properties: [
        new OA\Property(property: 'slaughter_plan_id', type: 'integer'),
        new OA\Property(property: 'actual_animals_slaughtered', type: 'integer', minimum: 0),
        new OA\Property(property: 'slaughter_time', type: 'string', format: 'date-time'),
        new OA\Property(property: 'status', type: 'string', enum: ['scheduled', 'in_progress', 'completed', 'cancelled']),
    ],
    type: 'object',
)]
final class SlaughterExecutionCreateRequest {}
