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
    path: '/api/v1/lookups',
    operationId: 'mobileLookups',
    summary: 'Facilities, inspectors, species, status enums for mobile forms',
    tags: ['Mobile API', 'Facilities', 'Inspectors', 'Businesses'],
    security: [['bearerAuth' => []]],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Nested lookup payload in `data` (facilities, inspectors, species, statuses).',
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
        new OA\Response(response: 200, description: 'Execution in standard success envelope.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess')),
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
