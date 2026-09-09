<?php

declare(strict_types=1);

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PostMortemCreateRequest',
    description: <<<'MD'
Create post-mortem inspection.

**FormRequest contract:** requires `slaughter_execution_id` (not `batch_id`). Counts: approved+condemned ≤ total_examined.
Provide species checklist `observations` and/or per-animal `item_outcomes`.

**Known mobile drift:** `MobileCollectionController::postMortemStore` currently scopes using `batch_id` from validated input,
while `StorePostMortemInspectionRequest` validates `slaughter_execution_id`. Prefer web post-mortem create until mobile is aligned.
MD,
    required: ['slaughter_execution_id', 'inspector_id', 'species', 'inspection_date', 'total_examined', 'approved_quantity', 'condemned_quantity'],
    properties: [
        new OA\Property(property: 'slaughter_execution_id', type: 'integer', example: 70),
        new OA\Property(property: 'inspector_id', type: 'integer'),
        new OA\Property(property: 'species', type: 'string', maxLength: 50),
        new OA\Property(property: 'inspection_date', type: 'string', format: 'date'),
        new OA\Property(property: 'total_examined', type: 'number', minimum: 0),
        new OA\Property(property: 'approved_quantity', type: 'number', minimum: 0),
        new OA\Property(property: 'condemned_quantity', type: 'number', minimum: 0),
        new OA\Property(property: 'notes', type: 'string', nullable: true, maxLength: 5000),
        new OA\Property(
            property: 'observations',
            description: 'Keyed by checklist item id; each value: { "value": string, "notes": string|null }.',
            type: 'object',
            nullable: true,
            example: ['carcass_condition' => ['value' => 'normal', 'notes' => null]],
        ),
        new OA\Property(
            property: 'item_outcomes',
            type: 'array',
            nullable: true,
            items: new OA\Items(type: 'object'),
            description: 'Per-animal outcomes when the execution has inspectable animals.',
        ),
    ],
    type: 'object',
)]
final class PostMortemCreateRequest {}
