<?php

declare(strict_types=1);

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Facility',
    description: 'Facility under a business (slaughterhouse, butchery, storage, …). FK: business_id.',
    required: ['id', 'business_id', 'facility_name', 'facility_type'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 3),
        new OA\Property(property: 'business_id', type: 'integer', example: 10),
        new OA\Property(property: 'facility_name', type: 'string', example: 'Main Plant'),
        new OA\Property(
            property: 'facility_type',
            type: 'string',
            enum: ['Slaughterhouse', 'Butchery', 'storage', 'Other'],
            example: 'Slaughterhouse',
        ),
        new OA\Property(property: 'license_number', type: 'string', nullable: true, example: 'LIC-2024-001'),
        new OA\Property(property: 'license_expiry_date', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'daily_capacity', type: 'integer', nullable: true, example: 120),
        new OA\Property(property: 'status', type: 'string', nullable: true),
    ],
    type: 'object',
)]
final class Facility {}
