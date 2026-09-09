<?php

declare(strict_types=1);

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'TransportTrip',
    description: 'Transport movement of certified product. Create requires an external destination (`destination_name`); `destination_facility_id` is prohibited on mobile/web store. Legacy rows may still have a facility destination.',
    required: ['id', 'certificate_id', 'origin_facility_id', 'destination_name', 'vehicle_plate_number', 'driver_name', 'departure_date', 'status'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 500),
        new OA\Property(property: 'certificate_id', type: 'integer', example: 300),
        new OA\Property(property: 'warehouse_storage_id', type: 'integer', nullable: true),
        new OA\Property(property: 'batch_id', type: 'integer', nullable: true, description: 'Optional; often derived from the certificate.'),
        new OA\Property(property: 'origin_facility_id', type: 'integer', example: 3),
        new OA\Property(property: 'destination_facility_id', type: 'integer', nullable: true, description: 'Legacy/internal facility destination; null for external destinations.'),
        new OA\Property(property: 'destination_name', type: 'string', example: 'Nairobi Cold Store'),
        new OA\Property(property: 'destination_country', type: 'string', nullable: true, maxLength: 2, example: 'KE', description: 'ISO 3166-1 alpha-2 from config processor.destination_countries.'),
        new OA\Property(property: 'destination_address', type: 'string', nullable: true),
        new OA\Property(property: 'vehicle_plate_number', type: 'string', example: 'RAB123C'),
        new OA\Property(property: 'driver_name', type: 'string', example: 'Jean Claude'),
        new OA\Property(property: 'driver_phone', type: 'string', nullable: true, example: '+250788000000'),
        new OA\Property(property: 'departure_date', type: 'string', format: 'date', example: '2026-04-22'),
        new OA\Property(property: 'arrival_date', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'status', type: 'string', enum: ['pending', 'in_transit', 'arrived', 'completed']),
    ],
    type: 'object',
)]
final class TransportTrip {}
