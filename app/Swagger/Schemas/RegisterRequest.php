<?php

declare(strict_types=1);

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'RegisterRequest',
    description: 'Mobile registration payload (same account rules as web registration, stateless JSON).',
    required: ['name', 'email', 'password', 'password_confirmation', 'business_type'],
    properties: [
        new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'John Doe'),
        new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 255, example: 'john@example.com'),
        new OA\Property(property: 'password', type: 'string', format: 'password', minLength: 8, example: 'Secret123!'),
        new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', minLength: 8, example: 'Secret123!'),
        new OA\Property(property: 'business_type', type: 'string', enum: ['farmer', 'processor', 'logistics'], example: 'processor'),
        new OA\Property(property: 'device_name', type: 'string', nullable: true, maxLength: 120, example: 'Android-Phone'),
    ],
    type: 'object',
)]
final class RegisterRequest {}
