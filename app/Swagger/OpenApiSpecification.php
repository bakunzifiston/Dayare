<?php

declare(strict_types=1);

namespace App\Swagger;

use OpenApi\Attributes as OA;

/**
 * Root OpenAPI document. Paths are defined alongside operation attribute classes under App\Swagger.
 */
#[OA\OpenApi(
    openapi: '3.0.0',
    info: new OA\Info(
        title: 'Butchapro API',
        version: '1.0.0',
        description: 'Traceability and compliance API for the meat processing system. JSON API base path: /api/v1. Auth: Bearer token from mobile_api_tokens. All JSON responses use `{ "success": true|false, "message": string, "data": object }` on success and `{ "success": false, "message": string, "errors": object }` on validation or domain errors (empty object when no field errors). Paginated lists expose Laravel\'s paginator array inside `data`.',
    ),
    servers: [
        new OA\Server(
            url: '/',
            description: 'Must be the site root only (e.g. http://127.0.0.1:8000 or "/"). Do NOT set the server to "/api" or "/api/v1" — every path below already starts with "/api/v1/...", and a wrong server will produce duplicate segments (404).',
        ),
    ],
    security: [['bearerAuth' => []]],
    tags: [
        new OA\Tag(
            name: 'Auth',
            description: 'Mobile API login and token lifecycle (`mobile_api_tokens`).',
        ),
        new OA\Tag(
            name: 'Users',
            description: 'Authenticated user profile for the mobile token (`/auth/me`).',
        ),
        new OA\Tag(
            name: 'Businesses',
            description: 'Tenant businesses (processor/farmer/logistics). Managed in the web workspace; see **Business** schema for persisted fields and ownership.',
        ),
        new OA\Tag(
            name: 'Facilities',
            description: 'Plants, slaughterhouses, storage sites. FK: `business_id`. License and capacity drive compliance checks.',
        ),
        new OA\Tag(
            name: 'Inspectors',
            description: 'Inspectors are scoped to a **facility** (`facility_id` required). Referenced by slaughter plans, batches, certificates.',
        ),
        new OA\Tag(
            name: 'Animal Intakes',
            description: 'Animal origin records before slaughter. Mobile: `GET/POST /api/v1/animal-intakes`. Full web create rules: `StoreAnimalIntakeRequest`.',
        ),
        new OA\Tag(
            name: 'Slaughter Plans',
            description: 'Plans link **facility**, **animal_intake**, **inspector**, species, and counts. Web validation (`StoreSlaughterPlanRequest`): intake must match facility; species must match intake; scheduled headcount ≤ remaining animals on intake; health certificate must not be expired; inspector must belong to facility; species must exist in `species` table.',
        ),
        new OA\Tag(
            name: 'Slaughter Executions',
            description: 'Actual slaughter events for a plan. FK: `slaughter_plan_id`.',
        ),
        new OA\Tag(
            name: 'Batches',
            description: 'Carcass/batch grouping after slaughter. FK: `slaughter_execution_id`, `inspector_id` (both required in domain model).',
        ),
        new OA\Tag(
            name: 'Ante Mortem Inspections',
            description: 'Pre-slaughter inspection with species-specific checklist (`AnteMortemChecklist`). Mobile: `POST /api/v1/ante-mortem-inspections`.',
        ),
        new OA\Tag(
            name: 'Post Mortem Inspections',
            description: 'Post-slaughter inspection and disposition. Mobile: `POST /api/v1/post-mortem-inspections`. FK: `batch_id`, `inspector_id`.',
        ),
        new OA\Tag(
            name: 'Certificates',
            description: 'Legal certificates. FK: `batch_id` nullable in storage edge cases; usually set. `inspector_id`, `facility_id` required for issuance.',
        ),
        new OA\Tag(
            name: 'Warehouse Storage',
            description: 'Cold storage rows. FK: `batch_id`, `certificate_id` required for traceability chain.',
        ),
        new OA\Tag(
            name: 'Transport Trips',
            description: 'Movement of product. FK: `certificate_id`, `origin_facility_id`, `destination_facility_id` required; `batch_id` nullable.',
        ),
        new OA\Tag(
            name: 'Delivery Confirmations',
            description: 'Receiving confirmation for a transport trip. FK: `transport_trip_id`, etc.',
        ),
        new OA\Tag(
            name: 'Compliance',
            description: 'Compliance dashboard aggregates facility/inspector/certificate/transport issues (expired licenses, missing inspections, capacity, etc.). **Implemented as HTML** at `GET /compliance` (session auth), not Bearer.',
        ),
        new OA\Tag(
            name: 'Traceability (public)',
            description: 'Public QR trace page: `GET /trace/{slug}` — no authentication.',
        ),
        new OA\Tag(
            name: 'Livestock',
            description: 'Farmer herd animals (`livestock` table, FK `farm_id`). Referenced alongside intakes and supply workflows.',
        ),
        new OA\Tag(
            name: 'AnimalHealthRecord',
            description: 'Farmer observation logs (`animal_health_records`); distinct from processor animal intakes.',
        ),
    ],
    components: new OA\Components(
        securitySchemes: [
            new OA\SecurityScheme(
                securityScheme: 'bearerAuth',
                type: 'http',
                scheme: 'bearer',
                bearerFormat: 'JWT',
                description: 'Opaque mobile API token issued by `POST /api/v1/auth/login` and validated against `mobile_api_tokens.token_hash` (SHA-256). Header: `Authorization: Bearer <token>`.',
            ),
        ],
    ),
)]
final class OpenApiSpecification {}
