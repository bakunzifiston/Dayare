<?php

declare(strict_types=1);

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'UserPublic',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Jane Processor'),
        new OA\Property(property: 'email', type: 'string', format: 'email'),
        new OA\Property(property: 'is_super_admin', type: 'boolean', example: false),
    ],
    type: 'object',
)]
final class UserPublic {}
