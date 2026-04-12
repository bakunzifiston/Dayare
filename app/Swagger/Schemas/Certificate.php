<?php

declare(strict_types=1);

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Certificate',
    description: 'Inspection certificate. batch_id may be nullable in edge data; normally links to batch. FK: inspector_id, facility_id.',
    required: ['id', 'inspector_id', 'facility_id', 'certificate_number', 'status'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 300),
        new OA\Property(property: 'batch_id', type: 'integer', nullable: true, description: 'Nullable in DB; usually set when issued from a batch.'),
        new OA\Property(property: 'inspector_id', type: 'integer', example: 5),
        new OA\Property(property: 'facility_id', type: 'integer', example: 3),
        new OA\Property(property: 'certificate_number', type: 'string', example: 'CERT-2026-0001'),
        new OA\Property(property: 'issued_at', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'expiry_date', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'status', type: 'string', enum: ['active', 'expired', 'revoked']),
    ],
    type: 'object',
)]
final class Certificate {}
