<?php

declare(strict_types=1);

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/api/v1',
    operationId: 'apiV1Index',
    summary: 'API v1 index',
    description: 'Public metadata and link to Swagger UI (`documentation` URL). No authentication.',
    tags: ['Mobile API', 'Auth'],
    security: [],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Standard envelope; `data` includes name, version, documentation.',
            content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'),
        ),
    ],
)]
#[OA\Post(
    path: '/api/v1/auth/login',
    operationId: 'mobileAuthLogin',
    summary: 'Issue mobile API token',
    description: 'Validates user email/password, creates a row in `mobile_api_tokens` (hashed token), returns plain token once. Token currently expires 30 days after issuance. No refresh endpoint exists; re-login is required after expiry.',
    tags: ['Mobile API', 'Auth'],
    security: [],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/LoginRequest'),
    ),
    responses: [
        new OA\Response(
            response: 200,
            description: 'Token issued inside standard envelope (see LoginResponse).',
            content: new OA\JsonContent(ref: '#/components/schemas/LoginResponse'),
        ),
        new OA\Response(
            response: 401,
            description: 'Invalid email or password (`success: false`, `message` describes failure).',
            content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
        ),
        new OA\Response(
            response: 422,
            description: 'Request body validation failure (e.g. missing email or password).',
            content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
        ),
        new OA\Response(
            response: 429,
            description: 'Too many login attempts (throttled).',
            content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
        ),
    ],
)]
#[OA\Post(
    path: '/api/v1/auth/register',
    operationId: 'mobileAuthRegister',
    summary: 'Register user and initial workspace (stateless)',
    description: 'Creates a user, assigns tenant owner role, creates a starter business (same rules as web registration). Returns Bearer token — no session cookie. Token currently expires 30 days after issuance. No refresh endpoint exists; re-login is required after expiry. Rate limited.',
    tags: ['Mobile API', 'Auth'],
    security: [],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/RegisterRequest'),
    ),
    responses: [
        new OA\Response(
            response: 201,
            description: 'Account created; token issued (see LoginResponse shape).',
            content: new OA\JsonContent(ref: '#/components/schemas/LoginResponse'),
        ),
        new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 429, description: 'Too many registration attempts (throttled).', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
    ],
)]
#[OA\Post(
    path: '/api/v1/auth/logout',
    operationId: 'mobileAuthLogout',
    summary: 'Revoke current mobile token',
    description: 'Invalidates only the bearer token used on this request. Other active tokens for the same user remain valid until their own expiry or logout.',
    tags: ['Mobile API', 'Auth'],
    security: [['bearerAuth' => []]],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Success; `data` is an empty object.',
            content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'),
        ),
        new OA\Response(response: 401, description: 'Missing or invalid Bearer token', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
    ],
)]
#[OA\Post(
    path: '/api/v1/businesses',
    operationId: 'mobileBusinessesStore',
    summary: 'Create a business (authenticated)',
    description: 'Same payload rules as web `StoreBusinessRequest`. User must own the account (creates under their user). Stateless JSON — no CSRF.',
    tags: ['Mobile API', 'Businesses'],
    security: [['bearerAuth' => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\MediaType(
            mediaType: 'application/json',
            schema: new OA\Schema(ref: '#/components/schemas/BusinessCreateRequest'),
        ),
    ),
    responses: [
        new OA\Response(response: 201, description: 'Business created in `data`.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess')),
        new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
    ],
)]
#[OA\Get(
    path: '/api/v1/auth/me',
    operationId: 'mobileAuthMe',
    summary: 'Current user for this token',
    description: 'Requires `Authorization: Bearer <token>`. Optional query `business_id` to resolve `userRole` / `business_type` for that workspace. In Swagger UI: click **Authorize**, enter the plain token from `POST /api/v1/auth/login` (the UI sends the Bearer prefix automatically).',
    tags: ['Mobile API', 'Users'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(
            name: 'business_id',
            in: 'query',
            required: false,
            description: 'Optional: resolve role/type for this business (must be accessible).',
            schema: new OA\Schema(type: 'integer', example: 12),
        ),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'User profile and accessible businesses inside standard envelope.',
            content: new OA\JsonContent(ref: '#/components/schemas/AuthMeResponse'),
        ),
        new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 404, description: 'Business not found or not accessible in current workspace scope.', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
    ],
)]
#[OA\Get(
    path: '/api/v1/dashboard',
    operationId: 'mobileDashboard',
    summary: 'Role-based processor dashboard KPI cards',
    tags: ['Mobile API', 'Dashboard', 'Businesses'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'period', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['all', 'day', 'month', 'year'])),
        new OA\Parameter(name: 'date_from', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
        new OA\Parameter(name: 'date_to', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Dashboard KPI payload in `data` (role, headerBadge, kpiCards, filters).',
            content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'),
        ),
        new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
    ],
)]
#[OA\Get(
    path: '/api/v1/lookups',
    operationId: 'mobileLookups',
    summary: 'Facilities, inspectors, species, status enums, and inspection checklists for mobile forms',
    tags: ['Mobile API', 'Facilities', 'Inspectors', 'Businesses'],
    security: [['bearerAuth' => []]],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Nested lookup payload in `data` (facilities, inspectors, species, statuses, ante_mortem_checklists, ante_mortem_checklist_meta, post_mortem_checklists, post_mortem_checklist_meta).',
            content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'),
        ),
        new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
    ],
)]
#[OA\Get(
    path: '/api/v1/animal-intakes',
    operationId: 'mobileAnimalIntakesIndex',
    summary: 'Paginated animal intakes for accessible facilities',
    tags: ['Mobile API', 'Animal Intakes', 'Businesses', 'Livestock', 'AnimalHealthRecord'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 20, minimum: 1, maximum: 100)),
        new OA\Parameter(name: 'facility_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        new OA\Parameter(name: 'species', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        new OA\Parameter(name: 'intake_date_from', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
        new OA\Parameter(name: 'intake_date_to', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Paginated envelope: `data.data` rows + `data.meta` + `data.filters`.',
            content: new OA\JsonContent(ref: '#/components/schemas/ApiPaginatedSuccess'),
        ),
        new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 404, description: 'Facility not found or outside current workspace scope.', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
    ],
)]
#[OA\Post(
    path: '/api/v1/animal-intakes',
    operationId: 'mobileAnimalIntakesStore',
    summary: 'Create animal intake',
    description: 'Facility must belong to the user\'s accessible businesses. See also StoreAnimalIntakeRequest for extended web validation (species enum, optional supplier/division/health cert fields).',
    tags: ['Mobile API', 'Animal Intakes', 'Businesses', 'Livestock', 'AnimalHealthRecord'],
    security: [['bearerAuth' => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/AnimalIntakeCreateRequest'),
    ),
    responses: [
        new OA\Response(
            response: 201,
            description: 'Created model in `data`.',
            content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'),
        ),
        new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 404, description: 'Referenced resource not found or outside current workspace scope.', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
    ],
)]
#[OA\Get(
    path: '/api/v1/animal-intakes/{animalIntake}',
    operationId: 'mobileAnimalIntakesShow',
    summary: 'Get one animal intake',
    tags: ['Mobile API', 'Animal Intakes', 'Businesses', 'Livestock', 'AnimalHealthRecord'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(
            name: 'animalIntake',
            in: 'path',
            required: true,
            description: 'Animal intake ID.',
            schema: new OA\Schema(type: 'integer'),
        ),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Intake in standard success envelope.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess')),
        new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 404, description: 'Resource not found or outside current workspace scope (ownership-protected).', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
    ],
)]
#[OA\Put(
    path: '/api/v1/animal-intakes/{animalIntake}',
    operationId: 'mobileAnimalIntakesUpdate',
    summary: 'Update animal intake',
    description: 'Uses UpdateAnimalIntakeRequest; same contract shape as create, scoped to accessible facilities.',
    tags: ['Mobile API', 'Animal Intakes', 'Businesses', 'Livestock', 'AnimalHealthRecord'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(
            name: 'animalIntake',
            in: 'path',
            required: true,
            schema: new OA\Schema(type: 'integer'),
        ),
    ],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/AnimalIntakeUpdateRequest'),
    ),
    responses: [
        new OA\Response(response: 200, description: 'Updated record in standard envelope.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess')),
        new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 404, description: 'Resource not found or outside current workspace scope (ownership-protected).', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
    ],
)]
#[OA\Post(
    path: '/api/v1/animal-intakes/{animalIntake}/submit',
    operationId: 'mobileAnimalIntakesSubmit',
    summary: 'Submit draft animal intake',
    description: 'Marks a draft intake as submitted (requires at least one animal line item). Sets status to approved and records `submitted_at`.',
    tags: ['Mobile API', 'Animal Intakes', 'Businesses', 'Livestock', 'AnimalHealthRecord'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'animalIntake', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Submitted intake in `data`.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess')),
        new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 404, description: 'Resource not found or outside current workspace scope (ownership-protected).', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 422, description: 'Not a draft or no animals on intake', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 500, description: 'Submit transaction failed', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
    ],
)]
#[OA\Delete(
    path: '/api/v1/animal-intakes/{animalIntake}',
    operationId: 'mobileAnimalIntakesDestroy',
    summary: 'Delete animal intake',
    tags: ['Mobile API', 'Animal Intakes', 'Businesses', 'Livestock', 'AnimalHealthRecord'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'animalIntake', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Deleted successfully.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess')),
        new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 404, description: 'Resource not found or outside current workspace scope (ownership-protected).', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 422, description: 'Cannot delete due to related records', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
    ],
)]
#[OA\Get(
    path: '/api/v1/slaughter-plans',
    operationId: 'mobileSlaughterPlansIndex',
    summary: 'Paginated slaughter plans',
    tags: ['Mobile API', 'Slaughter Plans'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 20, minimum: 1, maximum: 100)),
        new OA\Parameter(name: 'facility_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        new OA\Parameter(name: 'species', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        new OA\Parameter(name: 'slaughter_date_from', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
        new OA\Parameter(name: 'slaughter_date_to', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Paginated envelope: `data.data` rows + `data.meta` + `data.filters`.', content: new OA\JsonContent(ref: '#/components/schemas/ApiPaginatedSuccess')),
        new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 404, description: 'Facility not found or outside current workspace scope.', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
    ],
)]
#[OA\Post(
    path: '/api/v1/slaughter-plans',
    operationId: 'mobileSlaughterPlansStore',
    summary: 'Create slaughter plan',
    description: 'Uses StoreSlaughterPlanRequest rules: slaughter_date >= today; animal_intake must belong to facility; species must match intake and exist in `species`; inspector must belong to facility; intake health certificate not expired; number_of_animals_scheduled ≤ remaining animals on intake.',
    tags: ['Mobile API', 'Slaughter Plans'],
    security: [['bearerAuth' => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/SlaughterPlanCreateRequest'),
    ),
    responses: [
        new OA\Response(
            response: 201,
            description: 'Created plan in `data`.',
            content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'),
        ),
        new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 404, description: 'Referenced resource not found or outside current workspace scope.', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
    ],
)]
#[OA\Get(
    path: '/api/v1/slaughter-plans/{slaughterPlan}',
    operationId: 'mobileSlaughterPlansShow',
    summary: 'Get one slaughter plan',
    tags: ['Mobile API', 'Slaughter Plans'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'slaughterPlan', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Plan in standard success envelope.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess')),
        new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 404, description: 'Resource not found or outside current workspace scope (ownership-protected).', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
    ],
)]
#[OA\Put(
    path: '/api/v1/slaughter-plans/{slaughterPlan}',
    operationId: 'mobileSlaughterPlansUpdate',
    summary: 'Update slaughter plan',
    description: 'Uses UpdateSlaughterPlanRequest rules; same payload shape as create.',
    tags: ['Mobile API', 'Slaughter Plans'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'slaughterPlan', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/SlaughterPlanUpdateRequest'),
    ),
    responses: [
        new OA\Response(response: 200, description: 'Updated plan in standard envelope.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess')),
        new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 404, description: 'Resource not found or outside current workspace scope (ownership-protected).', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
    ],
)]
#[OA\Delete(
    path: '/api/v1/slaughter-plans/{slaughterPlan}',
    operationId: 'mobileSlaughterPlansDestroy',
    summary: 'Delete slaughter plan',
    tags: ['Mobile API', 'Slaughter Plans'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'slaughterPlan', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Deleted successfully.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess')),
        new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 404, description: 'Resource not found or outside current workspace scope (ownership-protected).', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 422, description: 'Cannot delete due to related records', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
    ],
)]
#[OA\Get(
    path: '/api/v1/slaughter-executions',
    operationId: 'mobileSlaughterExecutionsIndex',
    summary: 'Paginated slaughter executions',
    tags: ['Mobile API', 'Slaughter Executions'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 20, minimum: 1, maximum: 100)),
        new OA\Parameter(name: 'slaughter_plan_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        new OA\Parameter(name: 'slaughter_time_from', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
        new OA\Parameter(name: 'slaughter_time_to', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Paginated envelope: `data.data` rows + `data.meta` + `data.filters`.', content: new OA\JsonContent(ref: '#/components/schemas/ApiPaginatedSuccess')),
        new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 404, description: 'Slaughter plan not found or outside current workspace scope.', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
    ],
)]
#[OA\Post(
    path: '/api/v1/slaughter-executions',
    operationId: 'mobileSlaughterExecutionsStore',
    summary: 'Create slaughter execution',
    tags: ['Mobile API', 'Slaughter Executions'],
    security: [['bearerAuth' => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/SlaughterExecutionCreateRequest'),
    ),
    responses: [
        new OA\Response(
            response: 201,
            description: 'Created execution in `data`.',
            content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'),
        ),
        new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 404, description: 'Referenced resource not found or outside current workspace scope.', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
    ],
)]
#[OA\Get(
    path: '/api/v1/slaughter-executions/{slaughterExecution}',
    operationId: 'mobileSlaughterExecutionsShow',
    summary: 'Get one slaughter execution',
    tags: ['Mobile API', 'Slaughter Executions'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'slaughterExecution', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Execution in `data` with slaughter plan; includes `post_mortem_inspection` helper (inspectable animals, pending counts).', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess')),
        new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 404, description: 'Resource not found or outside current workspace scope (ownership-protected).', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
    ],
)]
#[OA\Put(
    path: '/api/v1/slaughter-executions/{slaughterExecution}',
    operationId: 'mobileSlaughterExecutionsUpdate',
    summary: 'Update slaughter execution',
    description: 'Uses UpdateSlaughterExecutionRequest rules; same payload shape as create.',
    tags: ['Mobile API', 'Slaughter Executions'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'slaughterExecution', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/SlaughterExecutionUpdateRequest'),
    ),
    responses: [
        new OA\Response(response: 200, description: 'Updated execution in standard envelope.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess')),
        new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 404, description: 'Resource not found or outside current workspace scope (ownership-protected).', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
    ],
)]
#[OA\Delete(
    path: '/api/v1/slaughter-executions/{slaughterExecution}',
    operationId: 'mobileSlaughterExecutionsDestroy',
    summary: 'Delete slaughter execution',
    tags: ['Mobile API', 'Slaughter Executions'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'slaughterExecution', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Deleted successfully.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess')),
        new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 404, description: 'Resource not found or outside current workspace scope (ownership-protected).', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 422, description: 'Cannot delete due to related records', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
    ],
)]
#[OA\Get(
    path: '/api/v1/monthly-inspection-reports',
    operationId: 'mobileMonthlyInspectionReportsIndex',
    summary: 'Paginated RICA monthly inspection reports for accessible facilities',
    tags: ['Mobile API', 'Monthly Inspection Reports', 'Facilities'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 20, minimum: 1, maximum: 100)),
        new OA\Parameter(name: 'facility_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['draft', 'submitted'])),
        new OA\Parameter(name: 'year', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        new OA\Parameter(name: 'month', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 12)),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Paginated envelope: `data.data` rows + `data.meta` + `data.filters`.', content: new OA\JsonContent(ref: '#/components/schemas/ApiPaginatedSuccess')),
        new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 404, description: 'Facility filter not found or outside current workspace scope.', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
    ],
)]
#[OA\Get(
    path: '/api/v1/monthly-inspection-reports/{facility}',
    operationId: 'mobileMonthlyInspectionReportsShow',
    summary: 'Full RICA monthly inspection report data for a facility and period',
    tags: ['Mobile API', 'Monthly Inspection Reports', 'Facilities'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'facility', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        new OA\Parameter(name: 'year', in: 'query', required: false, schema: new OA\Schema(type: 'integer', description: 'Defaults to current year')),
        new OA\Parameter(name: 'month', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 12, description: 'Defaults to current month')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Full report payload in `data` (facility_id, year, month, report{meta, received_animals, ante_mortem, post_mortem, meat_supply, closure, submission}).', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess')),
        new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 404, description: 'Facility not found or outside current workspace scope.', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
    ],
)]
#[OA\Post(
    path: '/api/v1/monthly-inspection-reports/{facility}/closure',
    operationId: 'mobileMonthlyInspectionReportsClosure',
    summary: 'Save or submit RICA monthly inspection report closure',
    tags: ['Mobile API', 'Monthly Inspection Reports', 'Facilities'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'facility', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'),
    ),
    responses: [
        new OA\Response(response: 200, description: 'Saved draft or submitted report record in `data`.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess')),
        new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 404, description: 'Facility not found or outside current workspace scope.', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 422, description: 'Validation or submission requirements not met', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
    ],
)]
#[OA\Get(
    path: '/api/v1/inspectors',
    operationId: 'mobileInspectorsIndex',
    summary: 'Paginated inspectors for accessible facilities',
    tags: ['Mobile API', 'Inspectors', 'Facilities'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 20, minimum: 1, maximum: 100)),
        new OA\Parameter(name: 'facility_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['active', 'expired'])),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Paginated envelope: `data.data` rows + `data.meta` + `data.filters`.', content: new OA\JsonContent(ref: '#/components/schemas/ApiPaginatedSuccess')),
        new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 404, description: 'Facility filter not found or outside current workspace scope.', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
    ],
)]
#[OA\Get(
    path: '/api/v1/inspectors/{inspector}',
    operationId: 'mobileInspectorsShow',
    summary: 'Show inspector',
    tags: ['Mobile API', 'Inspectors', 'Facilities'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'inspector', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Inspector in `data` with facility.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess')),
        new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 404, description: 'Resource not found or outside current workspace scope (ownership-protected).', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
    ],
)]
#[OA\Post(
    path: '/api/v1/inspectors',
    operationId: 'mobileInspectorsStore',
    summary: 'Register a new inspector',
    tags: ['Mobile API', 'Inspectors', 'Facilities'],
    security: [['bearerAuth' => []]],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess')),
    responses: [
        new OA\Response(response: 201, description: 'Created inspector in `data`.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess')),
        new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 404, description: 'Facility not found or outside current workspace scope.', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
    ],
)]
#[OA\Put(
    path: '/api/v1/inspectors/{inspector}',
    operationId: 'mobileInspectorsUpdate',
    summary: 'Update inspector',
    tags: ['Mobile API', 'Inspectors', 'Facilities'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'inspector', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess')),
    responses: [
        new OA\Response(response: 200, description: 'Updated inspector in `data`.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess')),
        new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 404, description: 'Resource not found or outside current workspace scope (ownership-protected).', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
    ],
)]
#[OA\Delete(
    path: '/api/v1/inspectors/{inspector}',
    operationId: 'mobileInspectorsDestroy',
    summary: 'Delete inspector',
    tags: ['Mobile API', 'Inspectors', 'Facilities'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'inspector', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Deleted successfully.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess')),
        new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 404, description: 'Resource not found or outside current workspace scope (ownership-protected).', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 422, description: 'Cannot delete due to related records', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
    ],
)]
#[OA\Get(
    path: '/api/v1/batches',
    operationId: 'mobileBatchesIndex',
    summary: 'Paginated batches for accessible slaughter executions',
    tags: ['Mobile API', 'Batches', 'Slaughter Executions'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 20, minimum: 1, maximum: 100)),
        new OA\Parameter(name: 'slaughter_execution_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        new OA\Parameter(name: 'species', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['pending', 'approved', 'rejected'])),
        new OA\Parameter(name: 'inspector_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        new OA\Parameter(name: 'cold_chain_status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['ok', 'at_risk', 'compromised'])),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Paginated envelope: `data.data` rows + `data.meta` + `data.filters`.', content: new OA\JsonContent(ref: '#/components/schemas/ApiPaginatedSuccess')),
        new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 404, description: 'Slaughter execution filter not found or outside current workspace scope.', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
    ],
)]
#[OA\Get(
    path: '/api/v1/batches/{batch}',
    operationId: 'mobileBatchesShow',
    summary: 'Show batch',
    tags: ['Mobile API', 'Batches', 'Slaughter Executions'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'batch', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Batch in `data` with items, post-mortem inspection, and certificates when present.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess')),
        new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 404, description: 'Resource not found or outside current workspace scope (ownership-protected).', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
    ],
)]
#[OA\Get(
    path: '/api/v1/ante-mortem-inspections',
    operationId: 'mobileAnteMortemIndex',
    summary: 'Paginated ante-mortem inspections for accessible slaughter plans',
    tags: ['Mobile API', 'Ante Mortem Inspections', 'Slaughter Plans'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 20, minimum: 1, maximum: 100)),
        new OA\Parameter(name: 'slaughter_plan_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        new OA\Parameter(name: 'species', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        new OA\Parameter(name: 'inspector_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        new OA\Parameter(name: 'inspection_date_from', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
        new OA\Parameter(name: 'inspection_date_to', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Paginated envelope: `data.data` rows + `data.meta` + `data.filters`.',
            content: new OA\JsonContent(ref: '#/components/schemas/ApiPaginatedSuccess'),
        ),
        new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 404, description: 'Slaughter plan filter not found or outside current workspace scope.', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
    ],
)]
#[OA\Get(
    path: '/api/v1/ante-mortem-inspections/{anteMortemInspection}',
    operationId: 'mobileAnteMortemShow',
    summary: 'Show ante-mortem inspection',
    tags: ['Mobile API', 'Ante Mortem Inspections', 'Slaughter Plans'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'anteMortemInspection', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Inspection in `data` with observations and item outcomes.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess')),
        new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 404, description: 'Resource not found or outside current workspace scope (ownership-protected).', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
    ],
)]
#[OA\Post(
    path: '/api/v1/ante-mortem-inspections',
    operationId: 'mobileAnteMortemStore',
    summary: 'Create ante-mortem inspection with checklist observations',
    tags: ['Mobile API', 'Ante Mortem Inspections', 'Slaughter Plans'],
    security: [['bearerAuth' => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/AnteMortemCreateRequest'),
    ),
    responses: [
        new OA\Response(response: 201, description: 'Created inspection in `data`.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess')),
        new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 404, description: 'Slaughter plan not found or outside current workspace scope.', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 422, description: 'Totals or checklist validation failed', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
    ],
)]
#[OA\Put(
    path: '/api/v1/ante-mortem-inspections/{anteMortemInspection}',
    operationId: 'mobileAnteMortemUpdate',
    summary: 'Update ante-mortem inspection',
    tags: ['Mobile API', 'Ante Mortem Inspections', 'Slaughter Plans'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'anteMortemInspection', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/AnteMortemCreateRequest'),
    ),
    responses: [
        new OA\Response(response: 200, description: 'Updated inspection in `data`.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess')),
        new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 404, description: 'Resource not found or outside current workspace scope (ownership-protected).', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
    ],
)]
#[OA\Delete(
    path: '/api/v1/ante-mortem-inspections/{anteMortemInspection}',
    operationId: 'mobileAnteMortemDestroy',
    summary: 'Delete ante-mortem inspection',
    tags: ['Mobile API', 'Ante Mortem Inspections', 'Slaughter Plans'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'anteMortemInspection', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Deleted successfully.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess')),
        new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 404, description: 'Resource not found or outside current workspace scope (ownership-protected).', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 422, description: 'Cannot delete due to related records', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
    ],
)]
#[OA\Get(
    path: '/api/v1/post-mortem-inspections',
    operationId: 'mobilePostMortemIndex',
    summary: 'Paginated post-mortem inspections for accessible batches',
    tags: ['Mobile API', 'Post Mortem Inspections', 'Batches'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 20, minimum: 1, maximum: 100)),
        new OA\Parameter(name: 'batch_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        new OA\Parameter(name: 'species', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        new OA\Parameter(name: 'inspector_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        new OA\Parameter(name: 'result', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['approved', 'partial', 'rejected'])),
        new OA\Parameter(name: 'inspection_date_from', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
        new OA\Parameter(name: 'inspection_date_to', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Paginated envelope: `data.data` rows + `data.meta` + `data.filters`.',
            content: new OA\JsonContent(ref: '#/components/schemas/ApiPaginatedSuccess'),
        ),
        new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 404, description: 'Batch filter not found or outside current workspace scope.', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
    ],
)]
#[OA\Get(
    path: '/api/v1/post-mortem-inspections/{postMortemInspection}',
    operationId: 'mobilePostMortemShow',
    summary: 'Show post-mortem inspection',
    tags: ['Mobile API', 'Post Mortem Inspections', 'Batches'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'postMortemInspection', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Inspection in `data` with observations and item outcomes.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess')),
        new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 404, description: 'Resource not found or outside current workspace scope (ownership-protected).', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
    ],
)]
#[OA\Post(
    path: '/api/v1/post-mortem-inspections',
    operationId: 'mobilePostMortemStore',
    summary: 'Create post-mortem inspection',
    tags: ['Mobile API', 'Post Mortem Inspections', 'Batches'],
    security: [['bearerAuth' => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/PostMortemCreateRequest'),
    ),
    responses: [
        new OA\Response(response: 201, description: 'Created inspection in `data`.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess')),
        new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 404, description: 'Batch not found or outside current workspace scope.', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 422, description: 'Totals or checklist validation failed', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
    ],
)]
#[OA\Put(
    path: '/api/v1/post-mortem-inspections/{postMortemInspection}',
    operationId: 'mobilePostMortemUpdate',
    summary: 'Update post-mortem inspection',
    tags: ['Mobile API', 'Post Mortem Inspections', 'Batches'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'postMortemInspection', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/PostMortemCreateRequest'),
    ),
    responses: [
        new OA\Response(response: 200, description: 'Updated inspection in `data`.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess')),
        new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 404, description: 'Resource not found or outside current workspace scope (ownership-protected).', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
    ],
)]
#[OA\Delete(
    path: '/api/v1/post-mortem-inspections/{postMortemInspection}',
    operationId: 'mobilePostMortemDestroy',
    summary: 'Delete post-mortem inspection',
    tags: ['Mobile API', 'Post Mortem Inspections', 'Batches'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'postMortemInspection', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Deleted successfully.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess')),
        new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 404, description: 'Resource not found or outside current workspace scope (ownership-protected).', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 422, description: 'Cannot delete due to related records', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
    ],
)]
#[OA\Get(
    path: '/api/v1/certificates',
    operationId: 'mobileCertificatesIndex',
    summary: 'Paginated certificates for accessible batches and facilities',
    tags: ['Mobile API', 'Certificates', 'Batches'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 20, minimum: 1, maximum: 100)),
        new OA\Parameter(name: 'batch_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        new OA\Parameter(name: 'facility_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        new OA\Parameter(name: 'inspector_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['active', 'expired', 'revoked'])),
        new OA\Parameter(name: 'issued_at_from', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
        new OA\Parameter(name: 'issued_at_to', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
        new OA\Parameter(name: 'certificate_number', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Paginated envelope: `data.data` rows + `data.meta` + `data.filters`.',
            content: new OA\JsonContent(ref: '#/components/schemas/ApiPaginatedSuccess'),
        ),
        new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 404, description: 'Batch or facility filter not found or outside current workspace scope.', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
    ],
)]
#[OA\Get(
    path: '/api/v1/certificates/{certificate}',
    operationId: 'mobileCertificatesShow',
    summary: 'Show certificate',
    tags: ['Mobile API', 'Certificates', 'Batches'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'certificate', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Certificate in `data` with batch, facility, inspector, and QR when present.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess')),
        new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 404, description: 'Resource not found or outside current workspace scope (ownership-protected).', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
    ],
)]
#[OA\Put(
    path: '/api/v1/certificates/{certificate}',
    operationId: 'mobileCertificatesUpdate',
    summary: 'Update certificate',
    tags: ['Mobile API', 'Certificates', 'Batches'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'certificate', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/CertificateCreateRequest'),
    ),
    responses: [
        new OA\Response(response: 200, description: 'Updated certificate in `data`.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess')),
        new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 404, description: 'Resource not found or outside current workspace scope (ownership-protected).', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 422, description: 'Validation or certificate eligibility error', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
    ],
)]
#[OA\Delete(
    path: '/api/v1/certificates/{certificate}',
    operationId: 'mobileCertificatesDestroy',
    summary: 'Delete certificate',
    tags: ['Mobile API', 'Certificates', 'Batches'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'certificate', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Deleted successfully.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess')),
        new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 404, description: 'Resource not found or outside current workspace scope (ownership-protected).', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 422, description: 'Cannot delete due to related records', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
    ],
)]
#[OA\Get(
    path: '/api/v1/certificates/{certificate}/qr',
    operationId: 'mobileCertificatesQr',
    summary: 'Certificate traceability QR payload',
    tags: ['Mobile API', 'Certificates', 'Batches'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'certificate', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    responses: [
        new OA\Response(response: 200, description: '`data` contains `slug`, `trace_url`, and `qr_svg`.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess')),
        new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 404, description: 'Resource not found or outside current workspace scope (ownership-protected).', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
    ],
)]
#[OA\Get(
    path: '/api/v1/certificates/{certificate}/pdf',
    operationId: 'mobileCertificatesPdf',
    summary: 'Download certificate PDF',
    tags: ['Mobile API', 'Certificates', 'Batches'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'certificate', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'PDF file download.', content: new OA\MediaType(mediaType: 'application/pdf')),
        new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 404, description: 'Resource not found or outside current workspace scope (ownership-protected).', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 422, description: 'PDF generation prerequisites not met', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
    ],
)]
#[OA\Post(
    path: '/api/v1/certificates',
    operationId: 'mobileCertificatesStore',
    summary: 'Issue certificate',
    description: 'Creates a certificate for a batch in scope. Batch must be eligible (post-mortem approved quantity > 0) and must not already have a certificate.',
    tags: ['Mobile API', 'Certificates', 'Batches'],
    security: [['bearerAuth' => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/CertificateCreateRequest'),
    ),
    responses: [
        new OA\Response(response: 201, description: 'Created certificate in `data`.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess')),
        new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 404, description: 'Batch not found or outside current workspace scope.', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 422, description: 'Validation or certificate eligibility error', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
    ],
)]
#[OA\Post(
    path: '/api/v1/warehouse-storages',
    operationId: 'mobileWarehouseStoragesStore',
    summary: 'Create warehouse storage record',
    description: 'Creates a cold-room storage entry. Certificate must be in workspace scope, active, and not already in storage.',
    tags: ['Mobile API', 'Warehouse Storage', 'Certificates', 'Batches'],
    security: [['bearerAuth' => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/WarehouseStorageCreateRequest'),
    ),
    responses: [
        new OA\Response(response: 201, description: 'Created storage record in `data`.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess')),
        new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 404, description: 'Facility or certificate not found / outside workspace scope.', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 422, description: 'Validation or business rule error', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
    ],
)]
#[OA\Get(
    path: '/api/v1/transport-trips',
    operationId: 'mobileTransportTripsIndex',
    summary: 'Paginated transport trips for accessible certificates',
    tags: ['Mobile API', 'Transport Trips', 'Certificates'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 20, minimum: 1, maximum: 100)),
        new OA\Parameter(name: 'certificate_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        new OA\Parameter(name: 'origin_facility_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        new OA\Parameter(name: 'destination_facility_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        new OA\Parameter(name: 'departure_date_from', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
        new OA\Parameter(name: 'departure_date_to', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Paginated envelope: `data.data` rows + `data.meta` + `data.filters`.', content: new OA\JsonContent(ref: '#/components/schemas/ApiPaginatedSuccess')),
        new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 404, description: 'Filter references resource outside workspace scope.', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
    ],
)]
#[OA\Get(
    path: '/api/v1/transport-trips/{transportTrip}',
    operationId: 'mobileTransportTripsShow',
    summary: 'Show transport trip',
    tags: ['Mobile API', 'Transport Trips', 'Certificates'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'transportTrip', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Trip in `data` with certificate and facilities.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess')),
        new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 404, description: 'Resource not found or outside current workspace scope (ownership-protected).', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
    ],
)]
#[OA\Post(
    path: '/api/v1/transport-trips',
    operationId: 'mobileTransportTripsStore',
    summary: 'Create transport trip',
    description: 'Creates a transport trip. Certificate and facilities must be in workspace scope; optional warehouse storage must be released.',
    tags: ['Mobile API', 'Transport Trips', 'Certificates', 'Warehouse Storage'],
    security: [['bearerAuth' => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/TransportTripCreateRequest'),
    ),
    responses: [
        new OA\Response(response: 201, description: 'Created transport trip in `data`.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess')),
        new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 404, description: 'Referenced resource not found or outside current workspace scope.', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 422, description: 'Validation or business rule error', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
    ],
)]
#[OA\Post(
    path: '/api/v1/delivery-confirmations',
    operationId: 'mobileDeliveryConfirmationsStore',
    summary: 'Create delivery confirmation',
    description: 'Creates a delivery confirmation for a transport trip in workspace scope. Client must be active if provided.',
    tags: ['Mobile API', 'Delivery Confirmations', 'Transport Trips'],
    security: [['bearerAuth' => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/DeliveryConfirmationCreateRequest'),
    ),
    responses: [
        new OA\Response(response: 201, description: 'Created delivery confirmation in `data`.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess')),
        new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 404, description: 'Referenced resource not found or outside current workspace scope.', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        new OA\Response(response: 422, description: 'Validation or business rule error', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
    ],
)]
final class ApiV1Operations {}
