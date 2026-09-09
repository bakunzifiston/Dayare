<?php

declare(strict_types=1);

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'DeliveryConfirmation',
    description: 'Confirms receipt for a transport trip. Receiver fields align with the trip destination. `receiving_facility_id` is not accepted on create (prohibited).',
    required: ['id', 'transport_trip_id', 'received_quantity', 'received_date', 'receiver_name', 'confirmation_status'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 600),
        new OA\Property(property: 'transport_trip_id', type: 'integer', example: 500),
        new OA\Property(property: 'receiving_facility_id', type: 'integer', nullable: true, description: 'Legacy; not settable via mobile create.'),
        new OA\Property(property: 'client_id', type: 'integer', nullable: true),
        new OA\Property(property: 'contract_id', type: 'integer', nullable: true),
        new OA\Property(property: 'received_quantity', type: 'integer', example: 24),
        new OA\Property(property: 'received_unit', type: 'string', enum: ['units', 'kg', 'g', 'tonnes', 'carcasses', 'boxes'], example: 'kg'),
        new OA\Property(property: 'received_date', type: 'string', format: 'date', example: '2026-04-23'),
        new OA\Property(property: 'receiver_name', type: 'string', example: 'Nairobi Cold Store'),
        new OA\Property(property: 'receiver_country', type: 'string', nullable: true, example: 'KE'),
        new OA\Property(property: 'receiver_address', type: 'string', nullable: true),
        new OA\Property(property: 'confirmation_status', type: 'string', enum: ['pending', 'confirmed', 'disputed']),
    ],
    type: 'object',
)]
final class DeliveryConfirmation {}
