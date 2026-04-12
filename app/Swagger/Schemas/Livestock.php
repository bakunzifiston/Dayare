<?php

declare(strict_types=1);

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Livestock',
    description: 'Farmer herd animal record. FK: farm_id.',
    required: ['id', 'farm_id'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 50),
        new OA\Property(property: 'farm_id', type: 'integer', example: 1),
        new OA\Property(property: 'tag_number', type: 'string', nullable: true, example: 'RFID-001'),
        new OA\Property(property: 'species', type: 'string', nullable: true, example: 'Cattle'),
        new OA\Property(property: 'quantity_healthy', type: 'integer', nullable: true),
        new OA\Property(property: 'quantity_sick', type: 'integer', nullable: true),
    ],
    type: 'object',
)]
final class Livestock {}
