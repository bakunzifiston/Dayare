<?php

declare(strict_types=1);

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'LoginSession',
    description: 'Payload inside success.data for POST /auth/login and POST /auth/register. `expires_at` defines when this token becomes invalid (protected routes then return 401 and client must re-login).',
    properties: [
        new OA\Property(property: 'token', type: 'string', example: '64-char-hex'),
        new OA\Property(property: 'token_type', type: 'string', example: 'Bearer'),
        new OA\Property(property: 'expires_at', type: 'string', format: 'date-time', nullable: true, example: '2026-05-22T10:30:00Z'),
        new OA\Property(property: 'user', ref: '#/components/schemas/UserPublic'),
    ],
    type: 'object',
)]
final class LoginSession {}
