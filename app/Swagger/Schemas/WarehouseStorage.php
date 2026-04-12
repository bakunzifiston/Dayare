<?php

declare(strict_types=1);

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'WarehouseStorage',
    description: 'Storage of certified product. FK: batch_id, certificate_id (domain rule); warehouse_facility_id, cold_room_id optional per flow.',
    required: ['id', 'batch_id', 'certificate_id'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 400),
        new OA\Property(property: 'warehouse_facility_id', type: 'integer', nullable: true),
        new OA\Property(property: 'cold_room_id', type: 'integer', nullable: true),
        new OA\Property(property: 'batch_id', type: 'integer', example: 200),
        new OA\Property(property: 'certificate_id', type: 'integer', example: 300),
        new OA\Property(property: 'entry_date', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'quantity_stored', type: 'number', format: 'float', nullable: true),
        new OA\Property(property: 'quantity_unit', type: 'string', nullable: true),
        new OA\Property(property: 'status', type: 'string', enum: ['in_storage', 'released', 'disposed']),
    ],
    type: 'object',
)]
final class WarehouseStorage {}
