<?php

declare(strict_types=1);

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'AnteMortemCreateRequest',
    description: 'observations must include every checklist item for the species; approved+rejected ≤ number_examined.',
    required: ['slaughter_plan_id', 'inspector_id', 'inspection_date', 'species', 'number_examined', 'number_approved', 'number_rejected', 'observations'],
    properties: [
        new OA\Property(property: 'slaughter_plan_id', type: 'integer'),
        new OA\Property(property: 'inspector_id', type: 'integer'),
        new OA\Property(property: 'inspection_date', type: 'string', format: 'date'),
        new OA\Property(property: 'species', type: 'string', maxLength: 50),
        new OA\Property(property: 'number_examined', type: 'integer', minimum: 0),
        new OA\Property(property: 'number_approved', type: 'integer', minimum: 0),
        new OA\Property(property: 'number_rejected', type: 'integer', minimum: 0),
        new OA\Property(property: 'notes', type: 'string', nullable: true),
        new OA\Property(
            property: 'observations',
            description: 'Keyed by checklist item id; each value: { "value": string (allowed per AnteMortemChecklist), "notes": string|null }',
            type: 'object',
            example: ['general_appearance' => ['value' => 'normal', 'notes' => null]],
        ),
    ],
    type: 'object',
)]
final class AnteMortemCreateRequest {}
