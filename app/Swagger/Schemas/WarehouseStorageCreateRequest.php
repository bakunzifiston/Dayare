<?php

declare(strict_types=1);

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'WarehouseStorageCreateRequest',
    description: 'Create cold-room storage record. Certificate must be in workspace scope, active, and not already in storage.',
    required: ['warehouse_facility_id', 'certificate_id', 'entry_date', 'quantity_stored', 'quantity_unit'],
    properties: [
        new OA\Property(property: 'warehouse_facility_id', type: 'integer', example: 4),
        new OA\Property(property: 'cold_room_id', type: 'integer', nullable: true, example: 12),
        new OA\Property(property: 'certificate_id', type: 'integer', example: 120),
        new OA\Property(property: 'entry_date', type: 'string', format: 'date', example: '2026-04-22'),
        new OA\Property(property: 'storage_location', type: 'string', nullable: true, maxLength: 255, example: 'Aisle B / Rack 3'),
        new OA\Property(property: 'temperature_at_entry', type: 'number', format: 'float', nullable: true, minimum: -50, maximum: 50, example: 3.5),
        new OA\Property(property: 'quantity_stored', type: 'integer', minimum: 1, example: 24),
        new OA\Property(property: 'quantity_unit', type: 'string', example: 'kg'),
    ],
    type: 'object',
)]
final class WarehouseStorageCreateRequest {}
