<?php

declare(strict_types=1);

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ApiError',
    description: 'Standard error envelope (validation or business rule failure).',
    required: ['success', 'message'],
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: false),
        new OA\Property(property: 'message', type: 'string', example: 'Error description'),
        new OA\Property(
            property: 'errors',
            description: 'Laravel-style field bag or arbitrary JSON object',
            type: 'object',
        ),
    ],
    type: 'object',
)]
final class ApiError {}
