<?php

declare(strict_types=1);

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Batch',
    description: 'Batch of product from a slaughter execution. FK: slaughter_execution_id, inspector_id (required).',
    required: ['id', 'slaughter_execution_id', 'inspector_id', 'species', 'batch_code'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 200),
        new OA\Property(property: 'slaughter_execution_id', type: 'integer', example: 70),
        new OA\Property(property: 'inspector_id', type: 'integer', example: 5),
        new OA\Property(property: 'species', type: 'string', example: 'Cattle'),
        new OA\Property(property: 'quantity', type: 'integer', nullable: true),
        new OA\Property(property: 'batch_code', type: 'string', example: 'BAT-20260411-A1B2C3'),
        new OA\Property(property: 'status', type: 'string', nullable: true),
        new OA\Property(property: 'cold_chain_status', type: 'string', nullable: true, enum: ['ok', 'at_risk', 'compromised']),
    ],
    type: 'object',
)]
final class Batch {}
