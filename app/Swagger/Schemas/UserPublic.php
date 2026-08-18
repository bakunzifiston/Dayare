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
        new OA\Property(property: 'userRole', type: 'string', description: 'Membership role for the selected business: org_admin, operations_manager, compliance_officer, inspector, transport_manager, accountant, super_admin, or user. This is the assigned role name, not the final access list.', example: 'inspector'),
        new OA\Property(property: 'business_type', type: 'string', nullable: true, description: 'Tenant type for active workspace', example: 'processor'),
        new OA\Property(property: 'business_id', type: 'integer', nullable: true, example: 12),
        new OA\Property(
            property: 'permissions',
            description: 'Effective processor permissions for the selected business after role defaults, per-business role customization, and per-user overrides. Empty for non-processor workspaces. Super administrators and business owners receive the full processor catalog. Mobile clients should gate features on this list, not only on userRole.',
            type: 'array',
            items: new OA\Items(type: 'string', example: 'issue_certificate'),
        ),
        new OA\Property(
            property: 'accessible_businesses',
            type: 'array',
            items: new OA\Items(
                type: 'object',
                properties: [
                    new OA\Property(property: 'id', type: 'integer'),
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'type', type: 'string'),
                    new OA\Property(property: 'membership', type: 'string', description: 'Assigned role: org_admin | operations_manager | compliance_officer | inspector | transport_manager | accountant'),
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
