<?php

declare(strict_types=1);

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'WarehouseStorageCreateRequest',
    description: <<<'MD'
Create cold-room storage from approved post-mortem inspection items (FormRequest contract).

Required: `warehouse_facility_id`, `post_mortem_inspection_item_ids` (min 1), `entry_date`, `quantity_unit`.
Optional: `cold_room_id` (required when the facility has cold rooms), `quantities` map keyed by item id, `temperature_at_entry`.

**Known mobile drift:** `MobileCollectionController::warehouseStoragesStore` still reads certificate-centric fields after validation.
Prefer the web cold-room flow until the mobile action is aligned with this request body.
MD,
    required: ['warehouse_facility_id', 'post_mortem_inspection_item_ids', 'entry_date', 'quantity_unit'],
    properties: [
        new OA\Property(property: 'warehouse_facility_id', type: 'integer', example: 4),
        new OA\Property(property: 'cold_room_id', type: 'integer', nullable: true, example: 12),
        new OA\Property(
            property: 'post_mortem_inspection_item_ids',
            type: 'array',
            items: new OA\Items(type: 'integer'),
            minItems: 1,
            example: [101, 102],
        ),
        new OA\Property(
            property: 'quantities',
            type: 'object',
            nullable: true,
            description: 'Optional map of post_mortem_inspection_item_id => quantity.',
            example: ['101' => 120.5],
            additionalProperties: new OA\AdditionalProperties(type: 'number'),
        ),
        new OA\Property(property: 'entry_date', type: 'string', format: 'date', example: '2026-04-22'),
        new OA\Property(property: 'temperature_at_entry', type: 'number', format: 'float', nullable: true, minimum: -50, maximum: 50, example: 3.5),
        new OA\Property(property: 'quantity_unit', type: 'string', example: 'kg'),
    ],
    type: 'object',
)]
final class WarehouseStorageCreateRequest {}
