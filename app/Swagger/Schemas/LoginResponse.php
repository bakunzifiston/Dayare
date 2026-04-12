<?php

declare(strict_types=1);

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'LoginResponse',
    description: 'Standard envelope for successful login.',
    required: ['success', 'message', 'data'],
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'message', type: 'string', example: 'Logged in successfully.'),
        new OA\Property(property: 'data', ref: '#/components/schemas/LoginSession'),
    ],
    type: 'object',
)]
final class LoginResponse {}
