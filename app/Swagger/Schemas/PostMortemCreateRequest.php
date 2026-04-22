<?php

declare(strict_types=1);

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PostMortemCreateRequest',
    description: 'approved+condemned ≤ total_examined; observations must cover PostMortemChecklist for species; abnormal critical items can force result rejected.',
    required: ['batch_id', 'inspector_id', 'species', 'inspection_date', 'total_examined', 'approved_quantity', 'condemned_quantity', 'observations'],
    properties: [
        new OA\Property(property: 'batch_id', type: 'integer'),
        new OA\Property(property: 'inspector_id', type: 'integer'),
        new OA\Property(property: 'species', type: 'string', maxLength: 50),
        new OA\Property(property: 'inspection_date', type: 'string', format: 'date'),
        new OA\Property(property: 'total_examined', type: 'integer', minimum: 0),
        new OA\Property(property: 'approved_quantity', type: 'integer', minimum: 0),
        new OA\Property(property: 'condemned_quantity', type: 'integer', minimum: 0),
        new OA\Property(property: 'notes', type: 'string', nullable: true, maxLength: 5000),
        new OA\Property(
            property: 'observations',
            description: 'Keyed by checklist item id; each value: { "value": string (max 20), "notes": string|null (max 5000) } per PostMortemChecklist.',
            type: 'object',
            example: ['carcass_condition' => ['value' => 'normal', 'notes' => null]],
        ),
    ],
    type: 'object',
)]
final class PostMortemCreateRequest {}
