<?php

declare(strict_types=1);

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'AuthMeResponse',
    description: 'Standard envelope for GET /auth/me.',
    required: ['success', 'message', 'data'],
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'message', type: 'string', example: 'OK'),
        new OA\Property(property: 'data', ref: '#/components/schemas/AuthMeData'),
    ],
    type: 'object',
)]
final class AuthMeResponse {}
