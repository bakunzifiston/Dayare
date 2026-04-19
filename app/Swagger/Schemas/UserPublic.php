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
        new OA\Property(property: 'userRole', type: 'string', description: 'Membership: owner, manager, staff, super_admin, or user', example: 'owner'),
        new OA\Property(property: 'business_type', type: 'string', nullable: true, description: 'Tenant type for active workspace', example: 'processor'),
        new OA\Property(property: 'business_id', type: 'integer', nullable: true, example: 12),
        new OA\Property(
            property: 'accessible_businesses',
            type: 'array',
            items: new OA\Items(
                type: 'object',
                properties: [
                    new OA\Property(property: 'id', type: 'integer'),
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'type', type: 'string'),
                    new OA\Property(property: 'membership', type: 'string', description: 'owner | manager | staff'),
                ],
            ),
        ),
        new OA\Property(
            property: 'accessible_business_ids',
            type: 'array',
            items: new OA\Items(type: 'integer'),
        ),
    ],
    type: 'object',
)]
final class UserPublic {}
