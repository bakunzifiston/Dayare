<?php

declare(strict_types=1);

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'TransportTripCreateRequest',
    description: 'Create transport trip. Certificate and facilities must be in workspace scope; optional warehouse storage must be released.',
    required: ['certificate_id', 'origin_facility_id', 'destination_facility_id', 'vehicle_plate_number', 'driver_name', 'departure_date', 'status'],
    properties: [
        new OA\Property(property: 'certificate_id', type: 'integer', example: 101),
        new OA\Property(property: 'warehouse_storage_id', type: 'integer', nullable: true, example: 55),
        new OA\Property(property: 'batch_id', type: 'integer', nullable: true, example: 88),
        new OA\Property(property: 'origin_facility_id', type: 'integer', example: 3),
        new OA\Property(property: 'destination_facility_id', type: 'integer', example: 4),
        new OA\Property(property: 'vehicle_plate_number', type: 'string', maxLength: 50, example: 'RAB123C'),
        new OA\Property(property: 'driver_name', type: 'string', maxLength: 255, example: 'Jean Claude'),
        new OA\Property(property: 'driver_phone', type: 'string', nullable: true, maxLength: 50, example: '+250788000000'),
        new OA\Property(property: 'departure_date', type: 'string', format: 'date', example: '2026-04-22'),
        new OA\Property(property: 'arrival_date', type: 'string', format: 'date', nullable: true, example: '2026-04-23'),
        new OA\Property(property: 'status', type: 'string', enum: ['pending', 'in_transit', 'arrived', 'completed'], example: 'pending'),
    ],
    type: 'object',
)]
final class TransportTripCreateRequest {}
