<?php

declare(strict_types=1);

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'RegisterRequest',
    required: ['name', 'email', 'password', 'password_confirmation', 'business_type'],
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'Jane Doe'),
        new OA\Property(property: 'email', type: 'string', format: 'email'),
        new OA\Property(property: 'password', type: 'string', format: 'password'),
        new OA\Property(property: 'password_confirmation', type: 'string', format: 'password'),
        new OA\Property(property: 'business_type', type: 'string', enum: ['farmer', 'processor', 'logistics']),
        new OA\Property(property: 'device_name', type: 'string', nullable: true, maxLength: 120),
    ],
    type: 'object',
)]
final class RegisterRequest {}
