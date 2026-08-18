<?php

declare(strict_types=1);

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'AuthMeData',
    description: 'Payload inside success.data for GET /auth/me.',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'email', type: 'string', format: 'email'),
        new OA\Property(property: 'is_super_admin', type: 'boolean'),
        new OA\Property(property: 'userRole', type: 'string', description: 'Assigned membership role for the selected business. Not the final access list.', example: 'inspector'),
        new OA\Property(property: 'business_type', type: 'string', nullable: true, example: 'processor'),
        new OA\Property(property: 'business_id', type: 'integer', nullable: true, example: 12),
        new OA\Property(
            property: 'permissions',
            description: 'Effective processor permissions for the selected business after role defaults, per-business role customization, and per-user overrides. Empty for non-processor workspaces. Super administrators and business owners receive the full processor catalog.',
            type: 'array',
            items: new OA\Items(type: 'string', example: 'record_ante_mortem'),
        ),
        new OA\Property(
            property: 'accessible_businesses',
            type: 'array',
            items: new OA\Items(type: 'object'),
        ),
        new OA\Property(
            property: 'accessible_business_ids',
            type: 'array',
            items: new OA\Items(type: 'integer'),
            example: [1, 2],
        ),
    ],
    type: 'object',
)]
final class AuthMeData {}
