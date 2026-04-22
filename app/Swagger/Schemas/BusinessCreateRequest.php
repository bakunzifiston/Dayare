<?php

declare(strict_types=1);

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'BusinessCreateRequest',
    description: 'Business create payload aligned to StoreBusinessRequest.',
    required: ['business_name', 'registration_number', 'contact_phone', 'email', 'status', 'owner_first_name', 'owner_last_name'],
    properties: [
        new OA\Property(property: 'type', type: 'string', enum: ['farmer', 'processor', 'logistics'], nullable: true, example: 'processor'),
        new OA\Property(property: 'business_name', type: 'string', maxLength: 255, example: 'Dayare Meat Ltd'),
        new OA\Property(property: 'registration_number', type: 'string', maxLength: 100, example: 'REG-2026-001'),
        new OA\Property(property: 'tax_id', type: 'string', maxLength: 100, nullable: true, example: 'TIN-20493'),
        new OA\Property(property: 'contact_phone', type: 'string', maxLength: 50, example: '+250788000000'),
        new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 255, example: 'info@dayaremeat.com'),
        new OA\Property(property: 'status', type: 'string', enum: ['active', 'suspended'], example: 'active'),
        new OA\Property(property: 'owner_first_name', type: 'string', maxLength: 255, example: 'Jean'),
        new OA\Property(property: 'owner_last_name', type: 'string', maxLength: 255, example: 'Claude'),
        new OA\Property(property: 'owner_dob', type: 'string', format: 'date', nullable: true, example: '1988-04-15'),
        new OA\Property(property: 'owner_gender', type: 'string', enum: ['male', 'female', 'other'], nullable: true, example: 'male'),
        new OA\Property(property: 'owner_pwd_status', type: 'string', enum: ['none', 'physical', 'visual', 'hearing', 'cognitive', 'other'], nullable: true, example: 'none'),
        new OA\Property(property: 'owner_phone', type: 'string', maxLength: 50, nullable: true, example: '+250788111111'),
        new OA\Property(property: 'owner_email', type: 'string', format: 'email', maxLength: 255, nullable: true, example: 'owner@dayaremeat.com'),
        new OA\Property(property: 'ownership_type', type: 'string', enum: ['sole_proprietor', 'partnership', 'company', 'cooperative', 'other'], nullable: true, example: 'company'),
        new OA\Property(property: 'business_size', type: 'string', enum: ['micro', 'small', 'medium', 'large'], nullable: true, example: 'small'),
        new OA\Property(property: 'baseline_revenue', type: 'integer', minimum: 0, nullable: true, example: 25000000),
        new OA\Property(property: 'vibe_unique_id', type: 'string', maxLength: 100, nullable: true, example: 'VIBE-01JRSK1F0D2H0NANAX8EYG4P79'),
        new OA\Property(property: 'vibe_commencement_date', type: 'string', format: 'date', nullable: true, example: '2026-04-20'),
        new OA\Property(property: 'pathway_status', type: 'string', enum: ['active', 'verification', 'inactive', 'graduated'], nullable: true, example: 'active'),
        new OA\Property(property: 'vibe_comments', type: 'string', maxLength: 1000, nullable: true, example: 'Initial onboarding complete.'),
        new OA\Property(property: 'country_id', type: 'integer', nullable: true, example: 1),
        new OA\Property(property: 'province_id', type: 'integer', nullable: true, example: 2),
        new OA\Property(property: 'district_id', type: 'integer', nullable: true, example: 3),
        new OA\Property(property: 'sector_id', type: 'integer', nullable: true, example: 4),
        new OA\Property(property: 'cell_id', type: 'integer', nullable: true, example: 5),
        new OA\Property(property: 'village_id', type: 'integer', nullable: true, example: 6),
        new OA\Property(property: 'city', type: 'string', maxLength: 100, nullable: true, example: 'Kigali'),
        new OA\Property(property: 'state_region', type: 'string', maxLength: 100, nullable: true, example: 'Kigali City'),
        new OA\Property(property: 'postal_code', type: 'string', maxLength: 20, nullable: true, example: '00000'),
        new OA\Property(property: 'country', type: 'string', maxLength: 100, nullable: true, example: 'Rwanda'),
        new OA\Property(
            property: 'members',
            type: 'array',
            nullable: true,
            items: new OA\Items(
                type: 'object',
                properties: [
                    new OA\Property(property: 'first_name', type: 'string', maxLength: 255, nullable: true),
                    new OA\Property(property: 'last_name', type: 'string', maxLength: 255, nullable: true),
                    new OA\Property(property: 'date_of_birth', type: 'string', format: 'date', nullable: true),
                    new OA\Property(property: 'gender', type: 'string', enum: ['male', 'female', 'other'], nullable: true),
                    new OA\Property(property: 'pwd_status', type: 'string', enum: ['none', 'physical', 'visual', 'hearing', 'cognitive', 'other'], nullable: true),
                    new OA\Property(property: 'phone', type: 'string', maxLength: 50, nullable: true),
                    new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 255, nullable: true),
                ],
            ),
        ),
    ],
    type: 'object',
)]
final class BusinessCreateRequest {}
