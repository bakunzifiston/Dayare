<?php

declare(strict_types=1);

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'TransportTripCreateRequest',
    description: <<<'MD'
Create transport trip for an active, non-expired certificate in workspace scope.

**Destination:** `destination_name` is required. `destination_facility_id` must not be sent (prohibited).
Optional `destination_country` is an ISO-2 code from `config('processor.destination_countries')` (e.g. RW, KE, TZ).
Optional `destination_address`.

**Certificate alignment:** When the certificate PDF / linked data locks vehicle, driver, destination, or departure fields,
the server overwrites those values from the certificate and rejects mismatches.
MD,
    required: ['certificate_id', 'origin_facility_id', 'destination_name', 'vehicle_plate_number', 'driver_name', 'departure_date', 'status'],
    properties: [
        new OA\Property(property: 'certificate_id', type: 'integer', example: 101),
        new OA\Property(property: 'batch_id', type: 'integer', nullable: true, description: 'Optional; auto-filled from certificate when omitted', example: 88),
        new OA\Property(property: 'origin_facility_id', type: 'integer', example: 3),
        new OA\Property(property: 'destination_name', type: 'string', maxLength: 255, example: 'Nairobi Cold Store'),
        new OA\Property(property: 'destination_country', type: 'string', nullable: true, maxLength: 2, example: 'KE', description: 'ISO 3166-1 alpha-2; stored uppercase.'),
        new OA\Property(property: 'destination_address', type: 'string', nullable: true, example: 'Industrial Area, Nairobi'),
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
