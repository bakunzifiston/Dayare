<?php

declare(strict_types=1);

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Business',
    description: 'Tenant business (farmer, processor, or logistics). FK: user_id (owner linkage).',
    required: ['id', 'type', 'business_name', 'status'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 10),
        new OA\Property(property: 'user_id', type: 'integer', nullable: true),
        new OA\Property(property: 'type', type: 'string', enum: ['farmer', 'processor', 'logistics'], example: 'processor'),
        new OA\Property(property: 'business_name', type: 'string', example: 'Dayare Meat Ltd'),
        new OA\Property(property: 'registration_number', type: 'string', nullable: true),
        new OA\Property(property: 'tax_id', type: 'string', nullable: true),
        new OA\Property(property: 'contact_phone', type: 'string', nullable: true),
        new OA\Property(property: 'email', type: 'string', format: 'email', nullable: true),
        new OA\Property(property: 'status', type: 'string', example: 'active'),
        new OA\Property(property: 'owner_gender', type: 'string', enum: ['male', 'female', 'other'], nullable: true),
        new OA\Property(property: 'owner_pwd_status', type: 'string', enum: ['none', 'physical', 'visual', 'hearing', 'cognitive', 'other'], nullable: true),
        new OA\Property(property: 'business_size', type: 'string', enum: ['micro', 'small', 'medium', 'large'], nullable: true),
        new OA\Property(
            property: 'baseline_revenue',
            type: 'string',
            enum: ['lt_2m', '2m_20m', '20m_100m', 'gt_101m'],
            nullable: true,
            example: '2m_20m',
        ),
        new OA\Property(property: 'vibe_unique_id', type: 'string', nullable: true, example: 'VIBE-01JRSK1F0D2H0NANAX8EYG4P79'),
        new OA\Property(property: 'vibe_commencement_date', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'pathway_status', type: 'string', enum: ['active', 'verification', 'inactive', 'graduated'], nullable: true),
        new OA\Property(property: 'vibe_comments', type: 'string', nullable: true),
        new OA\Property(property: 'address_line_1', type: 'string', nullable: true),
        new OA\Property(property: 'city', type: 'string', nullable: true),
        new OA\Property(property: 'country_id', type: 'integer', nullable: true),
    ],
    type: 'object',
)]
final class Business {}
