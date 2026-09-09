<?php

declare(strict_types=1);

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'DeliveryConfirmationCreateRequest',
    description: <<<'MD'
Create delivery confirmation for a transport trip in workspace scope.

**Receiver alignment:** When the trip has destination name/country/address, the server forces those into
`receiver_name` / `receiver_country` / `receiver_address` (locked fields). Do not send `receiving_facility_id` (prohibited).

Optional `client_id` must be an active client on an accessible business; `contract_id` must be in scope.
MD,
    required: ['transport_trip_id', 'received_quantity', 'received_date', 'receiver_name', 'confirmation_status'],
    properties: [
        new OA\Property(property: 'transport_trip_id', type: 'integer', example: 44),
        new OA\Property(property: 'client_id', type: 'integer', nullable: true, example: 21),
        new OA\Property(property: 'contract_id', type: 'integer', nullable: true, example: 16),
        new OA\Property(property: 'received_quantity', type: 'integer', minimum: 0, example: 24),
        new OA\Property(property: 'received_unit', type: 'string', enum: ['units', 'kg', 'g', 'tonnes', 'carcasses', 'boxes'], example: 'kg'),
        new OA\Property(property: 'received_date', type: 'string', format: 'date', example: '2026-04-23'),
        new OA\Property(property: 'receiver_name', type: 'string', maxLength: 255, example: 'Nairobi Cold Store', description: 'Overwritten from trip destination when locked.'),
        new OA\Property(property: 'receiver_country', type: 'string', nullable: true, maxLength: 100, example: 'KE', description: 'Usually ISO-2 from trip; overwritten when locked.'),
        new OA\Property(property: 'receiver_address', type: 'string', nullable: true, example: 'Industrial Area, Nairobi'),
        new OA\Property(property: 'confirmation_status', type: 'string', enum: ['pending', 'confirmed', 'disputed'], example: 'confirmed'),
    ],
    type: 'object',
)]
final class DeliveryConfirmationCreateRequest {}
