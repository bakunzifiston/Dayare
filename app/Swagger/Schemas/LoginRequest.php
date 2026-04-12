<?php

declare(strict_types=1);

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'LoginRequest',
    required: ['email', 'password'],
    properties: [
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'processor@example.com'),
        new OA\Property(property: 'password', type: 'string', format: 'password', example: 'secret'),
        new OA\Property(property: 'device_name', type: 'string', nullable: true, maxLength: 120, example: 'iPhone-15'),
    ],
    type: 'object',
)]
final class LoginRequest {}
