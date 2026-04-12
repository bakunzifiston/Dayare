<?php

declare(strict_types=1);

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'TransportTrip',
    description: 'Transport movement. FK: certificate_id, origin_facility_id, destination_facility_id required; batch_id nullable.',
    required: ['id', 'certificate_id', 'origin_facility_id', 'destination_facility_id'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 500),
        new OA\Property(property: 'certificate_id', type: 'integer', example: 300),
        new OA\Property(property: 'warehouse_storage_id', type: 'integer', nullable: true),
        new OA\Property(property: 'batch_id', type: 'integer', nullable: true, description: 'Optional link to batch when applicable.'),
        new OA\Property(property: 'origin_facility_id', type: 'integer', example: 3),
        new OA\Property(property: 'destination_facility_id', type: 'integer', example: 4),
        new OA\Property(property: 'vehicle_plate_number', type: 'string', nullable: true),
        new OA\Property(property: 'departure_date', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'status', type: 'string', enum: ['pending', 'in_transit', 'arrived', 'completed']),
    ],
    type: 'object',
)]
final class TransportTrip {}
