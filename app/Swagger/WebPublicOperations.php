<?php

declare(strict_types=1);

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/trace/{slug}',
    operationId: 'publicTraceabilityBySlug',
    summary: 'Public QR traceability page',
    description: 'Resolves `certificate_qrs.slug` → certificate with batch, slaughter execution, plan, animal intake origin chain, facility, inspector. **Response is HTML** (Blade), not JSON. No authentication. Tags list the domain areas surfaced on this page.',
    tags: [
        'Web Routes',
        'Traceability (public)',
        'Certificates',
        'Batches',
        'Animal Intakes',
        'Facilities',
        'Inspectors',
        'Slaughter Plans',
        'Slaughter Executions',
        'Ante Mortem Inspections',
        'Post Mortem Inspections',
    ],
    security: [],
    parameters: [
        new OA\Parameter(
            name: 'slug',
            in: 'path',
            required: true,
            description: 'Public slug from certificate QR record',
            schema: new OA\Schema(type: 'string', example: 'a1b2c3d4e5'),
        ),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'HTML page with certificate, batch, origin, facility context.',
            content: new OA\MediaType(
                mediaType: 'text/html',
                schema: new OA\Schema(type: 'string', format: 'html'),
            ),
        ),
        new OA\Response(response: 404, description: 'Unknown slug'),
    ],
)]
#[OA\Get(
    path: '/compliance',
    operationId: 'complianceDashboard',
    summary: 'Compliance monitoring dashboard (HTML)',
    description: 'Aggregates system issues for the signed-in processor user: expired facility licenses, inspector authorizations, over capacity, missing inspections, transport gaps, temperature issues, etc. **Requires Laravel web session** (not Bearer). Route lives outside `/api/v1`.',
    tags: [
        'Web Routes',
        'Compliance',
        'Certificates',
        'Warehouse Storage',
        'Transport Trips',
        'Delivery Confirmations',
        'Facilities',
        'Inspectors',
    ],
    security: [],
    responses: [
        new OA\Response(
            response: 200,
            description: 'HTML dashboard',
            content: new OA\MediaType(
                mediaType: 'text/html',
                schema: new OA\Schema(type: 'string', format: 'html'),
            ),
        ),
        new OA\Response(response: 302, description: 'Redirect to login when unauthenticated'),
    ],
)]
final class WebPublicOperations {}
