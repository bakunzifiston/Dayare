<?php

declare(strict_types=1);

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ApiSuccess',
    description: 'Standard success envelope (target contract for new and updated endpoints).',
    required: ['success', 'message', 'data'],
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'message', type: 'string', example: 'OK'),
        new OA\Property(property: 'data', type: 'object', description: 'Payload (model, array, or paginator).'),
    ],
    type: 'object',
)]
final class ApiSuccess {}
