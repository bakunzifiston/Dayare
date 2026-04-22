<?php

declare(strict_types=1);

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ApiPaginatedSuccess',
    description: 'Standard paginated success envelope for list endpoints.',
    required: ['success', 'message', 'data'],
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'message', type: 'string', example: 'OK'),
        new OA\Property(
            property: 'data',
            type: 'object',
            required: ['data', 'meta', 'filters'],
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(type: 'object'),
                ),
                new OA\Property(
                    property: 'meta',
                    type: 'object',
                    required: ['current_page', 'last_page', 'per_page', 'total'],
                    properties: [
                        new OA\Property(property: 'current_page', type: 'integer', example: 1),
                        new OA\Property(property: 'last_page', type: 'integer', example: 4),
                        new OA\Property(property: 'per_page', type: 'integer', example: 20),
                        new OA\Property(property: 'total', type: 'integer', example: 78),
                    ],
                ),
                new OA\Property(
                    property: 'filters',
                    description: 'Echoes query filters applied to this listing. Empty object when none.',
                    type: 'object',
                    additionalProperties: true,
                ),
            ],
        ),
    ],
    type: 'object',
)]
final class ApiPaginatedSuccess {}
