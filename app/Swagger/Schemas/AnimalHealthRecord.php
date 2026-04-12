<?php

declare(strict_types=1);

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'AnimalHealthRecord',
    description: 'Farmer-side health observation log. FK: farm_id, livestock_id.',
    required: ['id', 'farm_id', 'livestock_id', 'record_date', 'condition'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 900),
        new OA\Property(property: 'farm_id', type: 'integer', example: 1),
        new OA\Property(property: 'livestock_id', type: 'integer', example: 50),
        new OA\Property(property: 'record_date', type: 'string', format: 'date'),
        new OA\Property(property: 'condition', type: 'string', enum: ['healthy', 'sick']),
        new OA\Property(property: 'notes', type: 'string', nullable: true),
    ],
    type: 'object',
)]
final class AnimalHealthRecord {}
