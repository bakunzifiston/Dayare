# Mobile API Handoff

This handoff summarizes how mobile clients should integrate with the current `/api/v1` contract.

## Base Rules

- Base path: `/api/v1`
- Auth for protected routes: `Authorization: Bearer <token>`
- Success envelope:
  - `{ "success": true, "message": "...", "data": ... }`
- Error envelope:
  - `{ "success": false, "message": "...", "errors": {...} }`

## Authentication Lifecycle

- Login: `POST /api/v1/auth/login`
- Register: `POST /api/v1/auth/register`
- Current user: `GET /api/v1/auth/me`
- Logout (current token only): `POST /api/v1/auth/logout`

Token policy:
- Token is opaque and returned once on login/register.
- `expires_at` is returned in the auth payload.
- No refresh endpoint exists in current version.
- On `401`, client must re-login.

## Ownership and 404 Semantics

For protected `/api/v1/*` routes:
- `404` can mean either:
  - resource does not exist, or
  - resource is outside current workspace scope.

This is intentional to prevent cross-tenant enumeration.

## Pagination Contract (List Endpoints)

List endpoints return:

```json
{
  "success": true,
  "message": "OK",
  "data": {
    "data": [],
    "meta": {
      "current_page": 1,
      "last_page": 1,
      "per_page": 20,
      "total": 0
    },
    "filters": {}
  }
}
```

Important:
- Items are in `data.data`
- Pagination info is in `data.meta`
- Applied query filters are echoed in `data.filters`

## Core Mobile Endpoints

### Auth
- `POST /auth/login`
- `POST /auth/register`
- `GET /auth/me`
- `POST /auth/logout`

### Collections and Processing
- `GET/POST /animal-intakes`
- `GET/PUT/DELETE /animal-intakes/{animalIntake}`
- `GET/POST /slaughter-plans`
- `GET/PUT/DELETE /slaughter-plans/{slaughterPlan}`
- `GET/POST /slaughter-executions`
- `GET/PUT/DELETE /slaughter-executions/{slaughterExecution}`
- `POST /ante-mortem-inspections`
- `POST /post-mortem-inspections`

### Logistics Flow (Added)
- `POST /certificates`
- `POST /warehouse-storages`
- `POST /transport-trips`
- `POST /delivery-confirmations`

## Expected Error Handling on Mobile

- `401`: missing/invalid/expired token -> clear session and force login.
- `403`: authenticated but forbidden by policy/role.
- `404`: not found OR out of workspace scope.
- `422`: validation or business rule failure -> show field/global errors from `errors`.
- `429`: throttled -> retry with backoff.

## Swagger

Primary docs UI:
- `/api/documentation`

Raw docs:
- `/docs?api-docs.json`

Swagger config uses relative docs paths, so `127.0.0.1` and `localhost` host mismatch should no longer break docs loading.
