<?php

declare(strict_types=1);

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'DeliveryConfirmationCreateRequest',
    description: 'Create delivery confirmation for a transport trip in workspace scope.',
    required: ['transport_trip_id', 'received_quantity', 'received_date', 'receiver_name', 'confirmation_status'],
    properties: [
        new OA\Property(property: 'transport_trip_id', type: 'integer', example: 44),
        new OA\Property(property: 'receiving_facility_id', type: 'integer', nullable: true, example: 5),
        new OA\Property(property: 'client_id', type: 'integer', nullable: true, example: 21),
        new OA\Property(property: 'contract_id', type: 'integer', nullable: true, example: 16),
        new OA\Property(property: 'received_quantity', type: 'integer', minimum: 0, example: 24),
        new OA\Property(property: 'received_date', type: 'string', format: 'date', example: '2026-04-23'),
        new OA\Property(property: 'receiver_name', type: 'string', maxLength: 255, example: 'Warehouse Receiver'),
        new OA\Property(property: 'receiver_country', type: 'string', nullable: true, maxLength: 100, example: 'Rwanda'),
        new OA\Property(property: 'receiver_address', type: 'string', nullable: true, example: 'Kigali Special Economic Zone'),
        new OA\Property(property: 'confirmation_status', type: 'string', enum: ['pending', 'confirmed', 'disputed'], example: 'confirmed'),
    ],
    type: 'object',
)]
final class DeliveryConfirmationCreateRequest {}
