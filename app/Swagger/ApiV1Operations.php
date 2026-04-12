<?php

declare(strict_types=1);

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/api/v1',
    operationId: 'apiV1Index',
    summary: 'API v1 index',
    description: 'Public metadata and link to Swagger UI (`documentation` URL). No authentication.',
    tags: ['Auth'],
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
    description: 'Validates user email/password, creates a row in `mobile_api_tokens` (hashed token), returns plain token once.',
    tags: ['Auth'],
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
            response: 422,
            description: 'Invalid credentials or validation failure',
            content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
        ),
    ],
)]
#[OA\Post(
    path: '/api/v1/auth/logout',
    operationId: 'mobileAuthLogout',
    summary: 'Revoke current mobile token',
    tags: ['Auth'],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Success; `data` is an empty object.',
            content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'),
        ),
        new OA\Response(response: 401, description: 'Missing or invalid Bearer token'),
    ],
)]
#[OA\Get(
    path: '/api/v1/auth/me',
    operationId: 'mobileAuthMe',
    summary: 'Current user for this token',
    tags: ['Users'],
    responses: [
        new OA\Response(
            response: 200,
            description: 'User profile and accessible businesses inside standard envelope.',
            content: new OA\JsonContent(ref: '#/components/schemas/AuthMeResponse'),
        ),
        new OA\Response(response: 401, description: 'Unauthorized'),
    ],
)]
#[OA\Get(
    path: '/api/v1/lookups',
    operationId: 'mobileLookups',
    summary: 'Facilities, inspectors, species, status enums for mobile forms',
    tags: ['Facilities', 'Inspectors', 'Businesses'],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Nested lookup payload in `data` (facilities, inspectors, species, statuses).',
            content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'),
        ),
        new OA\Response(response: 401, description: 'Unauthorized'),
    ],
)]
#[OA\Get(
    path: '/api/v1/animal-intakes',
    operationId: 'mobileAnimalIntakesIndex',
    summary: 'Paginated animal intakes for accessible facilities',
    tags: ['Animal Intakes', 'Businesses', 'Livestock', 'AnimalHealthRecord'],
    parameters: [
        new OA\Parameter(
            name: 'per_page',
            in: 'query',
            schema: new OA\Schema(type: 'integer', default: 20),
        ),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Laravel length-aware paginator fields inside `data` (including `data` array of rows).',
            content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'),
        ),
        new OA\Response(response: 401, description: 'Unauthorized'),
    ],
)]
#[OA\Post(
    path: '/api/v1/animal-intakes',
    operationId: 'mobileAnimalIntakesStore',
    summary: 'Create animal intake',
    description: 'Facility must belong to the user\'s accessible businesses. See also StoreAnimalIntakeRequest for extended web validation (species enum, optional supplier/division/health cert fields).',
    tags: ['Animal Intakes', 'Businesses', 'Livestock', 'AnimalHealthRecord'],
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
        new OA\Response(response: 401, description: 'Unauthorized'),
        new OA\Response(response: 404, description: 'Facility not in scope'),
        new OA\Response(response: 422, description: 'Validation error'),
    ],
)]
#[OA\Get(
    path: '/api/v1/slaughter-plans',
    operationId: 'mobileSlaughterPlansIndex',
    summary: 'Paginated slaughter plans',
    tags: ['Slaughter Plans'],
    parameters: [
        new OA\Parameter(
            name: 'per_page',
            in: 'query',
            schema: new OA\Schema(type: 'integer', default: 20),
        ),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Paginator inside `data`.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess')),
        new OA\Response(response: 401, description: 'Unauthorized'),
    ],
)]
#[OA\Post(
    path: '/api/v1/slaughter-plans',
    operationId: 'mobileSlaughterPlansStore',
    summary: 'Create slaughter plan',
    description: 'Uses StoreSlaughterPlanRequest rules: slaughter_date >= today; animal_intake must belong to facility; species must match intake and exist in `species`; inspector must belong to facility; intake health certificate not expired; number_of_animals_scheduled ≤ remaining animals on intake.',
    tags: ['Slaughter Plans'],
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
        new OA\Response(response: 401, description: 'Unauthorized'),
        new OA\Response(response: 404, description: 'Facility not in scope'),
        new OA\Response(response: 422, description: 'Validation error'),
    ],
)]
#[OA\Get(
    path: '/api/v1/slaughter-executions',
    operationId: 'mobileSlaughterExecutionsIndex',
    summary: 'Paginated slaughter executions',
    tags: ['Slaughter Executions'],
    parameters: [
        new OA\Parameter(
            name: 'per_page',
            in: 'query',
            schema: new OA\Schema(type: 'integer', default: 20),
        ),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Paginator inside `data`.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess')),
        new OA\Response(response: 401, description: 'Unauthorized'),
    ],
)]
#[OA\Post(
    path: '/api/v1/slaughter-executions',
    operationId: 'mobileSlaughterExecutionsStore',
    summary: 'Create slaughter execution',
    tags: ['Slaughter Executions'],
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
        new OA\Response(response: 401, description: 'Unauthorized'),
        new OA\Response(response: 404, description: 'Plan not in scope'),
        new OA\Response(response: 422, description: 'Validation error'),
    ],
)]
#[OA\Post(
    path: '/api/v1/ante-mortem-inspections',
    operationId: 'mobileAnteMortemStore',
    summary: 'Create ante-mortem inspection with checklist observations',
    tags: ['Ante Mortem Inspections', 'Slaughter Plans'],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/AnteMortemCreateRequest'),
    ),
    responses: [
        new OA\Response(response: 201, description: 'Created inspection in `data`.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess')),
        new OA\Response(response: 401, description: 'Unauthorized'),
        new OA\Response(response: 404, description: 'Plan not in scope'),
        new OA\Response(response: 422, description: 'Totals or checklist validation failed', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
    ],
)]
#[OA\Post(
    path: '/api/v1/post-mortem-inspections',
    operationId: 'mobilePostMortemStore',
    summary: 'Create post-mortem inspection',
    tags: ['Post Mortem Inspections', 'Batches'],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/PostMortemCreateRequest'),
    ),
    responses: [
        new OA\Response(response: 201, description: 'Created inspection in `data`.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess')),
        new OA\Response(response: 401, description: 'Unauthorized'),
        new OA\Response(response: 404, description: 'Batch not in scope'),
        new OA\Response(response: 422, description: 'Totals or checklist validation failed', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
    ],
)]
final class ApiV1Operations {}
