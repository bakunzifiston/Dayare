<?php

declare(strict_types=1);

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CertificateCreateRequest',
    description: 'Aligned with StoreCertificateRequest. Batch must be in workspace scope, eligible for certificate, and without an existing certificate.',
    required: ['batch_id', 'inspector_id', 'facility_id', 'issued_at', 'status'],
    properties: [
        new OA\Property(property: 'batch_id', type: 'integer', example: 120),
        new OA\Property(property: 'inspector_id', type: 'integer', example: 5),
        new OA\Property(property: 'facility_id', type: 'integer', example: 3),
        new OA\Property(property: 'certificate_number', type: 'string', nullable: true, maxLength: 100, example: 'CERT-2026-0001'),
        new OA\Property(property: 'issued_at', type: 'string', format: 'date', example: '2026-04-22'),
        new OA\Property(property: 'expiry_date', type: 'string', format: 'date', nullable: true, example: '2026-10-22'),
        new OA\Property(property: 'status', type: 'string', enum: ['active', 'expired', 'revoked'], example: 'active'),
    ],
    type: 'object',
)]
final class CertificateCreateRequest {}
