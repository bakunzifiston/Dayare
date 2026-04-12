<?php

declare(strict_types=1);

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'DeliveryConfirmation',
    description: 'Confirms receipt for a transport trip. FK: transport_trip_id.',
    required: ['id', 'transport_trip_id'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 600),
        new OA\Property(property: 'transport_trip_id', type: 'integer', example: 500),
        new OA\Property(property: 'receiving_facility_id', type: 'integer', nullable: true),
        new OA\Property(property: 'received_quantity', type: 'number', format: 'float', nullable: true),
        new OA\Property(property: 'received_date', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'confirmation_status', type: 'string', enum: ['pending', 'confirmed', 'disputed']),
    ],
    type: 'object',
)]
final class DeliveryConfirmation {}
