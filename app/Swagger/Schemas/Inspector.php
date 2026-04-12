<?php

declare(strict_types=1);

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Inspector',
    description: 'Inspector attached to a single facility. FK: facility_id.',
    required: ['id', 'facility_id', 'first_name', 'last_name', 'status'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 5),
        new OA\Property(property: 'facility_id', type: 'integer', example: 3),
        new OA\Property(property: 'first_name', type: 'string', example: 'Jean'),
        new OA\Property(property: 'last_name', type: 'string', example: 'Inspector'),
        new OA\Property(property: 'authorization_number', type: 'string', nullable: true),
        new OA\Property(property: 'authorization_expiry_date', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'status', type: 'string', enum: ['active', 'expired'], example: 'active'),
    ],
    type: 'object',
)]
final class Inspector {}
