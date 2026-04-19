<?php

declare(strict_types=1);

namespace App\Swagger;

use OpenApi\Attributes as OA;

/**
 * Root OpenAPI document. Paths are defined alongside operation attribute classes under App\Swagger.
 *
 * This file mixes two audiences in one UI: use the **Mobile API** tag filter in Swagger UI for JSON
 * Bearer clients. **Web Routes** document session-based HTML/form flows — not callable with the
 * mobile token alone.
 */
#[OA\OpenApi(
    openapi: '3.0.0',
    info: new OA\Info(
        title: 'Butchapro API',
        version: '1.0.0',
        description: <<<'MD'
**This documentation includes both web and API routes.**

- **Mobile / API clients:** Only paths under `/api/v1/*` are intended for programmatic access. Use `Authorization: Bearer <token>` with a token from `POST /api/v1/auth/login`. Responses are JSON with envelope `{ "success": true|false, "message": string, "data": object }` (or `errors` on failure). Paginated lists nest Laravel’s paginator inside `data`.

- **Web routes:** Operations tagged **Web Routes** use Laravel **session** authentication (browser cookies), often return **HTML** or **redirects**, and are **not** substitutes for the mobile API.

**Filtering:** In Swagger UI, filter by tag **Mobile API** to see only Bearer JSON endpoints.

**Future REST:** Additional verbs (`GET/PATCH/DELETE` on resource IDs) may be added under `/api/v1` without changing the envelope contract.
MD
        ,
    ),
    servers: [
        new OA\Server(
            url: '/',
            description: 'Application root (e.g. https://buchapro.com/). Paths are absolute from this origin. Do NOT set the server to `/api` or `/api/v1` — paths already include `/api/v1/...`.',
        ),
    ],
    security: [['bearerAuth' => []]],
    tags: [
        new OA\Tag(
            name: 'Mobile API',
            description: '**JSON API for mobile and integrations** — routes under `/api/v1/*`. Authenticate with `Authorization: Bearer` (opaque token from login).',
        ),
        new OA\Tag(
            name: 'Web Routes',
            description: '**Browser session routes** — HTML or redirects; requires signed-in web user, not the mobile Bearer token.',
        ),
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
                description: '**Opaque API token** (not JWT). Obtain with `POST /api/v1/auth/login`; send once in the response `data.token`, then `Authorization: Bearer <token>`. Stored hashed as SHA-256 in `mobile_api_tokens.token_hash`. Expires per `expires_at` on the token row.',
            ),
        ],
    ),
)]
final class OpenApiSpecification {}
